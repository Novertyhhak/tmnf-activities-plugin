# TMNF activities plugin
A xaseco plugin for Trackmania Forever.\
Allows you to save activity data (timestamps) of players and later compare those together.\
Feel free to edit to your needs as long as you credit me.

## Purpose
If you have a server and want to check activity for a certain group of players, this plugin is perfect for you!

## Contact & support
**Discord** -  `Novertyhhak#4104` or [this server](https://discord.gg/BJzWRtw)\
**PayPal** - [paypal.me/Novertyhhak](https://paypal.me/Novertyhhak) ❤

## Disclaimer & warning
The plugin was tested on php 7.3 version. Things might misbehave if you use lower versions (like php 5).\
Some edge cases may have slipped under my eyes, please write me if something doesn't work as intended.

## Installation
1. Download the [latest release](https://github.com/Novertyhhak/tmnf-activities-plugin/archive/refs/heads/main.zip)
2. Unzip and move `plugin.activities.php` into `xaseco/plugins/`
3. Move `activities.xml` and `activities_logins.txt` into the root `xaseco` directory
4. Edit `plugins.xml` and include `<plugin>plugin.activities.php</plugin>`
5. Restart xaseco (`/admin shutdown`)

## Usage
![compareexample](https://raw.githubusercontent.com/Novertyhhak/tmnf-activities-plugin/main/activities_compare_example.png)

Set up the config file (`activities.xml`) to your needs.
If you use the LIST mode, write a list of logins you want to use. Do it either by hand or with the help of `/listassist` command.\
(Remember to remove example logins from the list. Also put each login in another line, like how its done in the example)\
Save a timestamp (see ```activities save``` below).\
Come back after some time (a week for example) and save another timestamp.\
Now compare your two timestamps (see ```activities compare``` below).\
If you don't remember how you have named your timestamps use ```/activities timestamps```

## Commands for /activities
Note: these commands can be used in any mode\
Note2: refferring to *the list* as the list of mode you set to (list, list of operators, list of everyone)\
Abbrev: `/acv` *(if enabled in the config)*

```/activities help``` - sents some help (activities commands, github link), *(permissions->help in config file)*

```/activities save <name>``` - saves stats to the **activity** table in a column named <name>. I recommend naming those timestamps with some date format, like YYYY_MM_DD. *(permissions->save in config file)*

```/activities timestamps``` - displays all your saved timestamps *(permissions->timestamps in config file)*

```/activities compare <older> <newer>``` - compares an older timestamp <older> to a newer one <newer> for each login in the list.\
Shows: *login / current average / improved average / compared time played / comp. most finishes / comp. visits*\
*(permissions->compare in config file)*
	
```/activities remove <name>``` - removes a timestamp you specified as the arg, *(permissions->remove in config file)*

```/activities laston``` - simply shows /laston for every login in the list in a window, *(permissions->laston in config file)*
	
```/activities list``` - displays the list, *(permissions->list in config file)*
	
## Commands for /listassist
Note: these commands can only be used in the LIST mode, one permission for all commands *(permissions->listassist in config file)*\
Abbrev: `/la` *(if enabled in the config)*

```/listassist help``` - sents some help (listassist commands, github link)

```/listassist add <login>``` - adds a login to the list
	
```/listassist remove <login>``` - removes a login to the list
	
```/listassist clear``` - clears the list

```/listassist reload``` - reloads the list

```/listassist backup <name>``` - backups the list to a backup folder
	
```/listassist listbackups``` - displays the list of your backups

```/listassist load <name>``` - loads a list, best to give full destination, for example `activities_backup/backup123.txt`, but the plugin can find the file even if you specify `backup123`.
	
```/listassist list``` - same as ```/actvities list```

## List file
Find the list file in the root directory of xaseco.\
Default file: `activities_logins.txt`, you can change it in the config file in the **thelist** tag.\
Put each login in new line.
	
## Config file
**abbrev** - enables abbrev commands (`/acv` and `/la`), before enabling, make sure no other plugin uses /acv and /la. `true/false`

**mode** - choose the mode to your needs:\
0 - LIST,  1 - OPERATORS,  2 - EVERYONE

**thelist** - list destination where you put login (for LIST mode), don't change it, unless you have a reason to.

**listbackupfolder** - folder name where the plugin saves backups, the plugin will automatically create the folder if it doesn't exist

**autoreloadlist** - if enabled, automatically reloads the list after using /listassist: add, remove, clear, load. `true/false`

### permissions:
0 - MasterAdmin,  1 - Admin,  2 - Operator

**help** - `/activities help`, isn't harmful so can be even enabled to operators\
**save** - `/activities save`, leave to people who know what they are doing (either MAdmins or Admins)\
**timestamps** - `/activities timestamps`, isn't harmful, but set it same as for **save**\
**compare** - `/activities compare`, isn't harmful, set it to whoever you want to see the comparision\
**remove** - `/activities remove`, leave it to people who know what they are doing (either MAdmins or Admins)\
**laston** - `/activities laston`, isn't harmful, set it to whoever you want to see the laston list\
**list** - `/activities list`, isn't harmful, set it to whoever you want to see list\
**listassist** - `/listassist` commands, leave to people who know what they are doing (either MAdmins or Admins)\
