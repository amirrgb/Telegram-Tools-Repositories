import loginManager
from tools import *
from contactManager import contacts,addContact

def sender(client):
    while True:
        n=input("1.with name\n2.with username\n")
        if "1" in n:
            name=input("name:")
            if name in contacts.keys():
                ok=input("is it %s?\n"%(convertor(contacts[name].first_name,printer=False)))
                if "y" in ok:
                    client.send_message(contacts[name],input("message:"))
                    print("sent")
            else:
                print("name not found")
        if "2" in n:
            username=input("username:")
            addContact(username,username)
            client.send_message(contacts[username],input("message:"))
            print("sent")


async def autoSender(client,contact,message):
    await client.send_message(contact,message)



async def allSender(contactsList):
    for contact in contactsList:
        await autoSender(loginManager.currentClient,contact,"سلام این پیام تستی میباشد")
