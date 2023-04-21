import loginManager


global contacts
contacts: dict = {}
global matloop


async def getAllContactsFromFile():
    with open("D://python/telegram/fastBot/contacts.txt",'r+')as f:
        for contact in f.readlines():
            try:
                contacts[contact.split(" : ")[0]] = await loginManager.currentClient.get_entity(contact.split(" : ")[1])
            except:print("error")
        contacts["me"] = await loginManager.currentClient.get_me()

async def addContact(name=None,username=None):
    if name is None:name = input("name :")
    if username is None:username = input("user name :")
    contacts[name] =await loginManager.currentClient.get_entity("t.me/%s"%username)
    with open("D://python/telegram/fastBot/contacts.txt",'a') as f:
        f.write("%s : %s\n"%(name,username))


async def searchContact(key=None):
    await getAllContactsFromFile()
    if key is None:
        key=input("what ?\n")
    for name,contact in contacts.items():
        if name == key or contact.username == key:
            return contact
    print("contact not found")
    return None



async def utContactManager():
    contactsList=[]
    index=1
    global matloop
    # with open("D://python/telegram/fastBot/utContacts.txt", 'r+') as f:
    #     for contact in f.readlines():
    #         if contact not in contactsList :
    #             contactsList.append(contact)
    # print("all unique contacts : ",len(contactsList))
    # for contact in contactsList:
    #     await addContact("ut%s"%1,contact)
    #     if index%20==0:
    #         print(index/20)
    #         index+=1
    matloop=[]
    for name,con in contacts.items():
        if "ut" in name:matloop.append(con)
    #
    matloop=[]
    await getAllContactsFromFile()
    await addContact("ali","shafiee_2003")
    await addContact("mamad","Mab1010")
    o=0
    for name,con in contacts.items():
        if "mamad" in name or "ali" in name :
            matloop.append(con)
            print(name)
    for name,con in contacts.items():
        matloop.append(con)
        if o==3:break
        o+=1

async def use2():
    await getAllContactsFromFile()
    n= input("1.add contact\n2.show all contact\n3.search contact :")
    if "1" in n:
        addContact()
    if "2" in n :
        for name,contact in contacts.items():
            print(name," -> ",contact.username)
    if "3" in n :
        searchContact()
