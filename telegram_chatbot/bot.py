from aiogram import Bot, Dispatcher, executor, types
#from aiogram.utils.emoji import emojize
#from aiogram.utils.markdown import bold, code, italic, text
from aiogram.types import ParseMode
bot = Bot(token='5627008289:AAEUVDNVkUxWzq8C8GacH8OMiZf1WOK6-yU')
dp = Dispatcher(bot)
print("start ....")
@dp.message_handler(commands=['start','help'])
async def send_welcome(message: types.Message):
    await message.reply("Hi!\nI'm Rajab Bot!\nPowered by amirrgb.")
    
@dp.message_handler()
async def echo(message: types.Message):
    listt=[1,2,3]
    keywords=["salam","Salam","hi","Hi","hello","Hello"]
    for keyword in keywords:
        if keyword in message.text:
            await message.reply("salaaam")
    if "bye" in message.text:
        await message.answer("bye")
    #await bot.send_message(message.chat.id, emojize(text(*listt, sep='\n')), parse_mode=ParseMode.MARKDOWN)
executor.start_polling(dp, skip_updates=True)
print("end ..")
exit()