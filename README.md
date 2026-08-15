# Moodle Availability Restriction: XP Store

This is a Moodle availability condition plugin (`availability_xpstore`) that works in tandem with the `local_xpstore` plugin. 

It allows teachers to restrict access to course activities and resources based on whether a student has "purchased" a specific item in the XP Store.

## Features
- **Seamless Integration**: Automatically syncs with products defined in `local_xpstore`.
- **Unlock Mechanics**: Works out of the box with the "Unlock" product type in the store. When a student buys an Unlock item, this availability condition is met.
- **Activity Settings**: Can be manually added from the "Restrict access" section of any Moodle activity.
- **Automated Injection**: If configured, `local_xpstore` can automatically inject this restriction into an activity without requiring the teacher to manually set it up.

## Installation
1. Ensure that `local_xpstore` is installed first.
2. Clone or download this repository.
3. Extract the contents into `moodle/availability/condition/xpstore`.
4. Log in as an administrator and go to **Site administration > Notifications** to complete the installation.

## Privacy
This plugin does not store any personal data. It only retrieves and checks transaction data managed natively by the `local_xpstore` plugin.
