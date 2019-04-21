# Frontenberg

A limited frontend preview of the Gutenberg editor, **not to be confused with a frontend editor**. It's literally the Gutenberg block editor post interface, but loaded on the frontend. It also includes some filters to prevent logged out users abusing APIs, but allowing Gutenberg to still function

Also, if you're logged into WP Admin, be careful, other users will see autosave notices and your changes will be saved!

## Installation

It's a WP theme, upload and activate and make sure you have the Gutenberg plugin installed and activated first.

## Can I Use This To Create Frontend Editors?

That's not what this was intended for. A lot of people mispronounce this as fronten**d**berg, but the goal of this is to do the following:

 - Load the WP Admin screen with the block editor, but on the frontend
 - Make it look like the needed REST API endpoints are working for logged out users
 - Make sure those endpoints don't do anything for logged out users
 
 Doing that sometimes requires the inclusion of WP Admin files, and can put WordPress into a strange state.
 
Now, having said that, it's possible you could undo the code for the latter two options, afterall the REST API editor context has little to do with the front or back end if everything's authenticated correctly. However, this theme has no styling for smushing the editor into a layout, and makes no guarantees that everything will work. Adding to that, it doesn't modify Gutenberg, it just triggers the code that loads it.

If you really want to put the block editor on the frontend, use the `<BlockEditor>` React component provided by the Gutenberg document, and use the relevant docs provided by the Gutenberg project

## Troubleshooting

This theme is not in any way production ready, outside of the thin scope of frontenberg.tomjn.com, you will almost certainly encounter issues. Also keep in mind that a lot of frontenberg issues are actually Gutenberg issues. Test in a local environment with the Gutenberg plugin first
