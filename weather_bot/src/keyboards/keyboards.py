from aiogram.types import InlineKeyboardButton, KeyboardButton, ReplyKeyboardMarkup
from aiogram.utils.keyboard import InlineKeyboardBuilder

def cities():
    builder = InlineKeyboardBuilder()
    builder.row(InlineKeyboardButton(text='Люберцы', callback_data='moscow'))
    builder.row(InlineKeyboardButton(text='Санкт-Петербург, ул. Думская', callback_data='petersburg'))
    builder.row(InlineKeyboardButton(text='Череповец, зашекснинский лес', callback_data='cherepovets'))
    builder.row(InlineKeyboardButton(text='ЭЭЭ МОЕГО ГОРОДА НЕТ', url='https://google.com'))
    return builder

