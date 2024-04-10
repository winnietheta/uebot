import asyncio
import logging

from aiogram import Bot, Dispatcher

from config import Config, load_config

from src.handlers import start
from src.handlers import moscow
from src.handlers import petersburg
from src.handlers import cherepovets


logger = logging.getLogger(__name__)


async def main():
    logging.basicConfig(
        level=logging.INFO,
        format="%(filename)s:%(lineno)d #%(levelname)-8s "
        "[%(asctime)s] - %(name)s - %(message)s",
    )

    logger.info("Starting bot")

    config: Config = load_config()

    bot: Bot = Bot(token=config.tg_bot.token, parse_mode="HTML")
    dp: Dispatcher = Dispatcher()

    dp.include_router(start.router)
    dp.include_router(moscow.router)
    dp.include_router(petersburg.router)
    dp.include_router(cherepovets.router)


    await bot.delete_webhook(drop_pending_updates=True)
    await dp.start_polling(bot)


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except (KeyboardInterrupt, SystemExit):
        logger.info("Bot stopped")
