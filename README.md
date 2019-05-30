# Frontenberg

A limited frontend preview of the Gutenberg editor, **not to be confused with a frontend editor**. It's literally the Gutenberg block editor post interface, but loaded on the frontend. It also includes some filters to prevent logged out users abusing APIs, but allowing Gutenberg to still function

Also, if you're logged into WP Admin, be careful, other users will see autosave notices and your changes will be saved!

## I Want To Put a Gutenberg Editor in My Theme

This is not the project for you. This is intended to act as a basic sandbox, not a frontend editor component.

### Is it Safe in Production?

Probably not, it was built specifically for https://frontenberg.tomjn.com, not as a general purpose block editor component.

### Can I Use it On a Client Site?

I strongly recommend against it

## Can I Use This To Create Frontend Editors?

Not really, it could be used that way, but that would be dangerous, and there are lots of known problems. **Don't use Frontenberg to create a working Frontend Editor**. Don't expect bug reports to be fixed either unless it impacts the core use of a sandbox e.g. frontenberg.tomjn.com testgutenberg.com or wordpress.org/gutenberg

### What Should I Use?

Use the `BlockProvider` and `BlockList` components provided by Gutenberg

### This Doesn't Work With XYZ

Good to know, but this project isn't supported for integrations with client work or other projects. This is a sandbox project.

## Installation

It's a WP theme, upload and activate and make sure you have the Gutenberg plugin installed and activated first.

## Troubleshooting

This theme is not in any way production ready, outside of the thin scope of frontenberg.tomjn.com, you will almost certainly encounter issues. Also keep in mind that a lot of frontenberg issues are actually Gutenberg issues. Test in a local environment with the Gutenberg plugin first
