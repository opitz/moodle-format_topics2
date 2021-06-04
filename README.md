# moodle-format_topics2
This course format is based on the standard Moodle Topics format but adds tab-abilties to it!

Initially the page rendering is identical to the standard Topics course format - it is then only in edit mode that you will discover some changes.

Assigining Topics to Tabs
-------------------------
To assign a topic to a tab you need to be in edit mode. Then from the topic edit menu chose one of the options named "To Tab ...". The topic then will immediately move there.
If this is the 1st topic assigned to a tab the tab will appear automatically (while removing the last topic from a tab will have it removed again). Clicking on it will show the assigned topic(s) and hide all others.

Initially tabs are named "Tab 1" to "Tab 5" but may be renamed (see below).

Only tabs with assigned (visible) topics will be shown.<br>
When tabs are shown and a page is loaded the left-most visible tab is always made the active one.

The "Module Content" tab
------------------------
When the 1st tab for a module is shown another tab - called "Module Content" by default - will show as well. This tab is different from other tabs in two ways: 
- It always contains all those topics that are not assigned to any other tab 
- and it stays in its place as the first (leftmost) tab (but may be hidden or invisible - see below!)

Renaming and swapping tabs
------------------------
Tabs may be renamed. To do so click on a tab name in edit mode, edit the name and press ENTER to save the changes or ESC to discard.

Tabs may swap places. To do so in edit mode drag one tab onto the tab you want to swap it with.<br>
Remember: You cannot swap places with the "Module Content" tab - but you may rename it.
  
Hidden Tabs
-----------
If a tab only contains topics that are hidden from students the tab itself will be hidden from students as well while being marked accordingly for course admins.

Single Topics Tabs
------------------
In the course settings for this format you may enable the option to treat single topics for a tab differently:
- When a topic is the single topic for a tab, the tab name will be replaced by the topic name.
- At the same time the standard header of the topic is - normally - hidden to avoid showing the same name twice.

<b>Please note</b> that when this option is set the first topic assigned to a tab will swap the tab name for the topic name - while assigning a second topic to the same tab will immediately change it back to the original tab name.
####Edit a Single Topic Tab name
You cannot edit a topic name shown as a tab name directly. For this reason only when in edit mode the topic header is indeed shown again - but slightly dimmed. You them may edit the topic name as usual. Any confirmed changes will then immediately be reflected in the tab name as well.

Section 0 always on top
---------
Being special Section-0 may be shown always on top of the tabs and other topics. Options for moving section 0 to "show always on top" or "show inline" with other topics may be chosen from the topic edit menu of Section-0.

Technical
---------
Almost all of the tab-ability is done by hiding, showing and moving page elements using Javascript/jQuery while the orignal rendering of the page remains identical to the one used by the non-tabbed course format.

This means that all other functionality of a course page remains intact: topics may be moved, renamed and edited as usual.
#####How does it work?
Tabs will have assigned the IDs of topics to them. When a tab is clicked ALL topics are first hidden and then all topics assigned to a tab will be shown.
For the "Module Content" tab the behavior is complementary: first all topics are shown and then all those assigned to any of the other tabs will be hidden again.

By default the format supports up to 5 tabs plus the "Module Content" tab (see above).
By setting $CFG->max_tabs in the config.php file this value may be changed up to a maximum of 10 tabs.

----
Version: topics-based v.20181210
