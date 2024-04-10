import requests
from aiogram import types, Router, Bot, F
from src.keyboards import keyboards
from aiogram.filters import Command
from aiogram.types import Message

router: Router = Router()
from config import Config, load_config

config = load_config()

@router.message(Command(commands=['start']))
async def start(message: Message):
    del_message = await message.delete()
    hw_message = await message.answer("<b>👋 Выберите город для просмотра погоды, нажав на кнопку ниже</b>", reply_markup=keyboards.cities().as_markup())
