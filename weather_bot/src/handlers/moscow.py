import requests
from aiogram import types, Router, Bot, F
from src.keyboards import keyboards
from aiogram.filters import Command
from aiogram.types import Message

router: Router = Router()
from config import Config, load_config

config = load_config()

@router.callback_query(F.data == 'moscow')
async def moscow(message: types.CallbackQuery):
    await message.answer()
    message = message.message
    hw_message = await message.answer("<b>🌆 Люберцы. Температура: 228 градусов Цельсия</b>", reply_markup=keyboards.cities().as_markup())
