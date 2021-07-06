# To-Do List PHP

A to-do list app that uses **unencrypted** files to store data. Not safe for anything else than self-hosted personal use. 
It requires no external libraries and can run off of PHP's built-in server if needed. 

Pull requests are welcome.

## Description

This is a to-do list that supports deadlines. 
It is a great way of organizing your thoughts and plans. 
The dates are "translated" to plain English, 
which means that the deadlines are written in relative names like Tomorrow, Yesterday, Next Tuesday, etc. 
This is not a scrum board like approach, more like a simplified calendar. 
A calendar that has no other function than to store your upcoming events and reminders. 
Unlike other calendars, all features are removed that would obscure the at-a-glance overview aspect of all notes. 
It is not meant for archiving or very long term (or recurring) events, but for planning the week. 

While it is not secure enough to use it in public, it could be used by multiple people simultaneously. 
The app fully supports user separation. Each user can have unlimited backups of their lists that can be easily restored.

As a possible daily-driver to-do app that could serve as your home page, 
it features categorization so that certain events can be viewed as distinct notes and will never get mixed up. 

Editing notes is possible, but just like the "snooze" button on alarms, there are quick-access buttons for delaying reminders by a day or a week. 

## Features

- Users
- Backups
- Categorization
- Note editing
- Quick delaying without manually editing
- Friendly dates (Tomorrow, Yesterday, Next Tuesday, etc.)

## Requirements

- PHP 7.4.10 or higher
