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
The plugin was tested on php 7.3 version.\
Some edge cases may have slipped under my eyes, please write me if something doesn't work as intended.

## Instalation
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
Save a timestamp (see ```activity save``` below).\
Come back after some time (a week for example) and save another timestamp.\
Now compare those two (see ```activity compare``` below).\
If you don't remember how you have named your timestamps use ```/activity timestamps```

## activities commands
Note: those can be used in any mode
Abbrev: `/acv` (if enabled in the config)

```/activities help``` - sents some help (activities commands, github link), *(permissions->help in config file)*

```/activities save <name>``` - saves stats to the **activity** table in a column named <name>. I recommend naming those timestamps with some date format, like YYYY_MM_DD. *(permissions->save in config file)*

```/activities timestamps``` - displays all your saved timestamps *(permissions->timestamps in config file)*

```/activities compare <older> <newer>``` - compares an older timestamp <older> to a newer one <newer> for each login in the list.\
Shows: *login / current average / improved average / compared time played / comp. most finishes / comp. visits*\
*(permissions->compare in config file)*
	
```/activities remove <name>``` - removes a timestamp you specified as the arg, *(permissions->remove in config file)*

```/activities laston``` - simply shows /laston for every login in the list in a window, *(permissions->laston in config file)*
	
```/activities list``` - displays the list, *(permissions->list in config file)*
	
## listassist commands
Note: those can only be used in the LIST mode, one permission for all commands *(permissions->listassist in config file)*
Abbrev: `/la` (if enabled in the config)

```/listassist help``` - sents some help (listassist commands, github link)

```/listassist add <login>``` - adds a login to the list
	
```/listassist remove <login>``` - removes a login to the list
	
```/listassist clear``` - clears the list

```/listassist reload``` - reloads the list

```/listassist backup <name>``` - backups the list to a backup folder
	
```/listassist listbackups``` - displays the list of your backups

```/listassist load <name>``` - loads a list, best to give full destination, for example `activities_backup/backup123.txt`, but the plugin can find the file even if you specify `backup123`.
	
```/listassist list``` - same as ```/actvities list```
	
