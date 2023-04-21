import loginManager
from loginManager import login
from contactManager import addContact,searchContact,contacts
from datetime import datetime, timedelta
from getMessage import autogeter
from sendMessage import autoSender
def wantedClients():
    wClients=[]
    for i in range(int(input("how many account you wants : "))):
        wClients.append(login())
    return wClients

def wantedContacts():
    wContacts:dict={}
    for i in range(int(input("how many contacts you will send or get :"))):
        user=input("who : ")
        wContacts[user]=(searchContact(user))
    return wContacts

def wantedSendMessages():
    wMessages=[]
    for i in range(int(input("how many message you wanna send :"))):
        wMessages.append(input("message : "))
    return wMessages

def wantedTimes():
    wTimes=[]
    for i in range(int(input("how many times you will send :"))):
        time = datetime.strptime(input("in this format Hour:Min:Second"), '%H:%M:%S').date()
        wTimes.append(time)
    wTimes.sort()
    return wTimes

def wantedKeywords():
    keys=[]
    while True:
        if "e" == input("your keywords (e -> end)"):break
        else:keys.append(input)
    return keys

def messageChecker(messages,keys):
    for message in messages:
        for key in keys:
            if key in message:
                return True
    return False

def timeChecker(times):
    now = datetime.now().time()
    for time in times :
        if time >= now:
            if time - now <= timedelta(milliseconds=500):
                return True
        elif now - time <= timedelta(milliseconds=500):
            return True
    return False

def fastBotPro():
    wClients=wantedClients()
    wContacts=wantedContacts()
    wMessages=wantedSendMessages()
    wTimes=wantedTimes()
    wKeys=wantedKeywords()
    for client in wClients:
        client.start()
        for contact in wContacts:
            if messageChecker(autogeter(client,contact),wKeys):
                for message in wMessages:
                    autoSender(client,contact,message)
            if timeChecker(wTimes):
                for message in wMessages:
                    autoSender(client,contact,message)

async def fastBot():
    await loginManager.currentClient.start()
    name=input("name of contact: ")
    contact = await searchContact(name)
    if contact is None:contact = await addContact(name=name)
    i = 3
    while i!=0 :
        print("waiting for code ..... ")
        all = await autogeter(loginManager.currentClient,contact,1)
        for m in all:
            if "کد دارم" in str(m.message) or "کد ناهار" in str(m.message) or "کد فراموشی" in str(m.message):
                await autoSender(loginManager.currentClient,contact,"استفاده میکنم")
                print("i got it ")
                i-=1