import requests
from aiogram import types, Router, Bot, F
from src.keyboards import keyboards
from aiogram.filters import Command
from aiogram.types import Message

router: Router = Router()
from config import Config, load_config

config = load_config()

@router.callback_query(F.data == 'cherepovets')
async def cherepovets(message: types.CallbackQuery):
    await message.answer()
    message = message.message
    weather = requests.get("https://api.open-meteo.com/v1/forecast?latitude=59.090469&longitude=37.893580&current=temperature_2m,wind_speed_10m&hourly=temperature_2m,relative_humidity_2m,wind_speed_10m").json()
    hw_message = await message.answer("<b>🌆 Череповец. Температура: " + str(weather['current']['temperature_2m']) + " градусов Цельсия</b>", reply_markup=keyboards.cities().as_markup())
