import contactManager
import loginManager
from contactManager import addContact,contacts,utContactManager
from fastBotMaker import fastBot

from getUsers import getAllParticipantsAllGroup
from loginManager import use1
from sendMessage import allSender

use1()

with loginManager.currentClient as client:
        client.loop.run_until_complete(utContactManager())
        client.loop.run_until_complete(allSender(contactManager.matloop))
        #client.loop.run_until_complete(getAllParticipantsAllGroup(loginManager.currentClient))

