from telethon.tl.functions.channels import GetParticipantsRequest
from tools import *
from contactManager import contacts, addContact, searchContact
from telethon.tl.types import (PeerChannel, ChannelParticipantsSearch)

async def getAllParticipantsAllGroup(client):
    ids = []
    while True:
        ids.append(await getAllParticipants(client))
        if "ok" in input("end ?"):break
    try:
        ids = list(dict(ids))
    except:
        for id in ids:
            if ids.count(id)>=2:
                ids.remove(id)
                ids.append(id)
    with open("D://python/telegram/fastBot/utContacts.txt", 'a') as f:
        for tel_id in ids:
            f.write("%s\n" % tel_id)

async def getAllParticipants(client):
    offset = 0
    limit = 1000
    all_participants = []
    await addContact()
    my_channel = await searchContact()
    while True:
        participants = await client(
            GetParticipantsRequest(my_channel, ChannelParticipantsSearch(''), offset, limit, hash=0))
        if not participants.users:
            break
        all_participants.extend(participants.users)
        offset += len(participants.users)
    print(len(all_participants))
    idList = []
    for participant in all_participants:
        tel_id = str(participant.id) if participant.id is not None else " "
        first = str(participant.first_name) if participant.first_name is not None else " "
        last = str(participant.last_name) if participant.last_name is not None else " "
        username = str(participant.username) if participant.username is not None else " "
        idList.append(username)
    return idList
