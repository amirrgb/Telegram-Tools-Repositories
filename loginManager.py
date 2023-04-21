import configparser
import json
import asyncio
import os
import arabic_reshaper
from datetime import date, datetime
from bidi.algorithm import get_display
from telethon import TelegramClient
from telethon.errors import SessionPasswordNeededError
from telethon.tl.functions.messages import GetHistoryRequest
from telethon.tl.types import PeerChannel
global clients
clients :dict={}
global currentClient
currentClient:TelegramClient
dir_path='D://python/telegram/fastBot/configss'

def numberOfAccount():
    return len([entry for entry in os.listdir(dir_path) if os.path.isfile(os.path.join(dir_path, entry))])

def configMaker(api_id,api_hash,phone,username,displayName):
    i=numberOfAccount()+1
    config = configparser.ConfigParser()
    config.add_section('Telegram')
    config['Telegram']['api_id'] = api_id
    config['Telegram']['api_hash'] = api_hash
    config['Telegram']['phone'] = phone
    config['Telegram']['username'] = username
    config['Telegram']['name'] = displayName
    with open('%s/config%s.ini'%(dir_path,i),'w') as configfile:  # save
        config.write(configfile)

def getAllClients():
    for i in range(numberOfAccount()):
        config = configparser.ConfigParser()
        print('%s/config%s.ini'%(dir_path,i+1))
        config.read('%s/config%s.ini'%(dir_path,i+1))
        clients[config['Telegram']['name']]=TelegramClient(config['Telegram']['username'],api_id=config['Telegram']['api_id'],api_hash=config['Telegram']['api_hash'])

def signUp():
    api_id=input('api_id :')
    api_hash=input('api_hash :')
    username=input('username :')
    phone=input('phone :')
    displayName=input('display name :')
    configMaker(api_id,api_hash,phone,username,displayName)
    clients[displayName] = TelegramClient(username, api_id, api_hash)
    clients[displayName].start()
    print("Client Created")
    if clients[displayName].is_user_authorized() == False:
        clients[displayName].send_code_request(phone)
        try:
            clients[displayName].sign_in(phone, input('Enter the code: '))
        except SessionPasswordNeededError:
            clients[displayName].sign_in(password=input('Password: '))
    return clients[displayName]

def login(showAll=None):
    getAllClients()
    own = input("please give me pass to access Clients : ")
    if "ok" == own:
        if showAll is None:
            showAll = input("write any thing to search (1.show all):")
            for name, client in clients.items():
                text = name + " : " + str(client.api_id)
                if showAll == "1" or showAll in text:
                    print(text)
            showAll = input("which name ? ")
    for name,client in clients.items():
        if name == showAll:
            return client

def use1():
    while True:
        n1=input("1.sign up\n2.sign in\n3.end")
        global currentClient
        if "1" == n1:
            currentClient = signUp()
        else:
            if "2" == n1:currentClient = login()
            if "3" == n1:break
            if "4" == n1:currentClient=login("haghPanah")
            break

