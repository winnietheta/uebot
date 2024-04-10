<?php

class Uebot
{
    // Vars
    public string $python_bot_dir;
    public string $json_bot_file;

    public function __construct()
    {
        $this->python_bot_dir = __DIR__ . '/example/'; // telegram python bot path
        $this->json_bot_file = __DIR__ . '/bot.json'; // json bot path
    }

    private function tab(int $k): string // creates tabs
    {
        $str = '';
        for ($i = 1; $i <= $k; $i++) {
            $str .= '    ';
        }
        return $str;
    }

    private function process_python_vars(string $string): array|string|null // replaces [[var]] to python string concat var
    {
        return preg_replace('/\|\|(.*?)\|\|/', '" + str($1) + "', $string);
    }

    private function process_python_keyboard(string $keyboard_name): string // convert keyboard name to reply_markup arg
    {
        if ($keyboard_name != 'None') {
            $reply_markup = 'keyboards.' . $keyboard_name . '().as_markup()';
        } else {
            $reply_markup = 'None';
        }
        return $reply_markup;
    }

    private function process_action($cursor, $handler_code, $action) // processes handler actions
    {
        switch ($action['type']) {
            case 'send_message':
                $reply_markup = $this->process_python_keyboard($action['data']['reply_markup']);
                $action['data']['message_text'] = $this->process_python_vars($action['data']['message_text']);
                $handler_code .= $this->tab($cursor) . 'bot = Bot(token=config.tg_bot.token, parse_mode="HTML")' . "\n";
                $handler_code .= $this->tab($cursor) . $action['var'] . ' = await bot.send_message(' . $action['data']['chat_id'] . ', "' . $action['data']['message_text'] . '", reply_markup=' . $reply_markup . ')' . "\n";
                break;
            case 'answer_text':
                $reply_markup = $this->process_python_keyboard($action['data']['reply_markup']);
                $action['data']['message_text'] = $this->process_python_vars($action['data']['message_text']);
                $handler_code .= $this->tab($cursor) . $action['var'] . ' = await message.answer("' . $action['data']['message_text'] . '", reply_markup=' . $reply_markup . ')' . "\n";
                break;
            case 'delete_handler_message':
                $handler_code .= $this->tab($cursor) . $action['var'] . ' = await message.delete()' . "\n";
                break;
            case 'http_get_json':
                $handler_code .= $this->tab($cursor) . $action['var'] . ' = requests.get("' . $action['url'] . '").json()' . "\n";
                break;
        }
        return $handler_code;
    }

    public function convert_json_to_python(): void // main function
    {
        // Convert JSON bot to array
        $array_bot = json_decode(file_get_contents($this->json_bot_file), true);

        // Update bot token
        file_put_contents($this->python_bot_dir . '/.env', 'BOT_TOKEN=' . $array_bot['bot_token']);

        // Update handler imports
        $handler_imports = '';
        $handler_includes = '';

        foreach ($array_bot['handlers'] as $handler) {
            $handler_imports .= 'from src.handlers import ' . $handler['name'] . "\n";
            $handler_includes .= $this->tab(1) . 'dp.include_router(' . $handler['name'] . '.router)' . "\n";
        }

        $bot_py = file_get_contents($this->python_bot_dir . '/bot.example.py');
        $bot_py = str_replace('[[HANDLER_IMPORTS]]', $handler_imports, $bot_py);
        $bot_py = str_replace('[[HANDLER_INCLUDES]]', $handler_includes, $bot_py);

        file_put_contents($this->python_bot_dir . '/bot.py', $bot_py);

        // Create keyboards file
        $keyboards_code = '';
        foreach ($array_bot['keyboards'] as $keyboard) {
            $keyboards_code .= "from aiogram.types import InlineKeyboardButton, KeyboardButton, ReplyKeyboardMarkup\nfrom aiogram.utils.keyboard import InlineKeyboardBuilder\n\n";
            $keyboards_code .= "def " . $keyboard['name'] . "():\n";
            if ($keyboard['type'] == 'inline') {
                $keyboards_code .= $this->tab(1) . "builder = InlineKeyboardBuilder()\n";
            }
            foreach ($keyboard['buttons'] as $button) {
                if ($button['type'] == 'callback') {
                    $keyboards_code .= $this->tab(1) . "builder.row(InlineKeyboardButton(text='" . $button['data']['button_text'] . "', callback_data='" . $button['data']['callback_data'] . "'))\n";
                }

                if ($button['type'] == 'url') {
                    $keyboards_code .= $this->tab(1) . "builder.row(InlineKeyboardButton(text='" . $button['data']['button_text'] . "', url='" . $button['data']['url'] . "'))\n";
                }
            }
            $keyboards_code .= $this->tab(1) . "return builder\n\n";
        }

        file_put_contents($this->python_bot_dir . '/src/keyboards/keyboards.py', $keyboards_code);

        // Update handlers
        foreach ($array_bot['handlers'] as $handler) {
            $handler_code = "import requests\nfrom aiogram import types, Router, Bot, F\nfrom src.keyboards import keyboards\nfrom aiogram.filters import Command\nfrom aiogram.types import Message\n\nrouter: Router = Router()\nfrom config import Config, load_config\n\nconfig = load_config()\n\n";
            $cursor = 1;
            switch ($handler['type']) {
                case 'command':
                    $handler_code .= "@router.message(Command(commands=['" . $handler['name'] . "']))\nasync def " . $handler['name'] . "(message: Message):\n";
                    foreach ($handler['actions'] as $action) {
                        $handler_code = $this->process_action($cursor, $handler_code, $action);
                        file_put_contents($this->python_bot_dir . '/src/handlers/' . $handler['name'] . '.py', $handler_code);
                    }
                    break;
                case 'callback':
                    $handler_code .= "@router.callback_query(F.data == '" . $handler['name'] . "')\nasync def " . $handler['name'] . "(message: types.CallbackQuery):\n" . $this->tab(1) . "await message.answer()\n" . $this->tab(1) . "message = message.message\n";
                    foreach ($handler['actions'] as $action) {
                        $handler_code = $this->process_action($cursor, $handler_code, $action);
                        file_put_contents($this->python_bot_dir . '/src/handlers/' . $handler['name'] . '.py', $handler_code);
                    }
                    break;
            }
        }
    }
}
