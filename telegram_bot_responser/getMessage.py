from tools import *
from contactManager import contacts,addContact

def geter(client):
    while True:
        n=input("1.with name\n2.with username\n")
        if "1" in n:
            name=input("name:")
            if name in contacts:
                ok=input("is it %s?\n"%(convertor(contacts[name].first_name,printer=False)))
                if "y" in ok:
                    messages = client.get_messages(contacts[name], limit=int(input("how many messages :")))
                    for message in messages:
                        print(message.message)
            else:
                print("name not found")
        if "2" in n:
            username=input("username:")
            addContact(username,username)
            messages = client.get_messages(contacts[username], limit=int(input("how many messages :")))
            for message in messages:
                print(message.message)


async def autogeter(client:TelegramClient,contact,limit:int=1):
    messages = await client.get_messages(contact, limit=limit)
    all=[]
    for m in messages:
        all.append(m)
    return all