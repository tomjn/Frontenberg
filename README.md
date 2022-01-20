# Frontenberg Classic

A limited frontend preview of the Gutenberg editor, **not to be confused with a frontend editor**. It's literally the Gutenberg block editor post interface, but loaded on the frontend. It also includes some filters to prevent logged out users abusing APIs, but allowing Gutenberg to still function

Also, if you're logged into WP Admin, be careful, other users will see autosave notices and your changes will be saved!

**!! Frontenberg is not a frontend editor !!** and cannot be used to create a frontend editor.

## I Want To Put the Gutenberg Editor in My Theme

This is not the project for you. This is intended to act as a basic sandbox for demonstrating the block editor without installing WP or logging in, it is not a frontend editor component.

### Is it Safe in Production?

**Probably not**, it was built specifically for https://frontenberg.tomjn.com, not as a general purpose block editor component.

There are a lot of restrictions that try to lock down Frontenberg, if any of these are lifted to allow the creation or updating of posts, bugs will occur, and security will be compromised. For example, early versions of Frontenberg allowed people to create terms and other unwanted data on the site that had to be cleaned up and locked down.

### Can I Use it On a Client Site?

Do not do this, it is a terrible and dangerous idea.

## Can I Use This To Create Frontend Editors?

No, that would be dangerous, and there are lots of known problems. **Don't try to use Frontenberg to create a Frontend Editor**. Don't expect bug reports to be fixed either unless it impacts the core use of a sandbox e.g. frontenberg.tomjn.com testgutenberg.com or wordpress.org/gutenberg

### What Should I Use Instead Then To Create Frontend Editors?

Use the `BlockProvider` and `BlockList` components provided by Gutenberg

### This Doesn't Work With XYZ

Good to know, but this project isn't supported for integrations with client work or other projects. This is a sandbox project.

## Installation

It's a WP theme, upload and activate and make sure you have the Gutenberg plugin installed and activated first.

## Troubleshooting

This theme is not in any way production ready, outside of the thin scope of frontenberg.tomjn.com, you will almost certainly encounter issues. Also keep in mind that a lot of frontenberg issues are actually Gutenberg issues. Test in a local environment with the Gutenberg plugin first
