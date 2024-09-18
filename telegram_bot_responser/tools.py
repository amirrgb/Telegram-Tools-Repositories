import configparser
import json
import asyncio
import arabic_reshaper
from datetime import date, datetime
from bidi.algorithm import get_display
from telethon import TelegramClient
from telethon.errors import SessionPasswordNeededError
from telethon.tl.functions.messages import GetHistoryRequest
from telethon.tl.types import PeerChannel


#for persian and arabic
def convertor(text:str,printer=True):
    words=text.split()
    for word in words[::-1]:
        if printer:
            print(get_display(arabic_reshaper.reshape(word)),end=" ")
        else:
            return get_display(arabic_reshaper.reshape(word))


# some functions to parse json date
class DateTimeEncoder(json.JSONEncoder):
    def default(self, o):
        if isinstance(o, datetime):
            return o.isoformat()

        if isinstance(o, bytes):
            return list(o)

        return json.JSONEncoder.default(self, o)

