# My Multi Author Plugin

WordPress plugin that allows you to attribute multiple authors to single post.

## Roadmap

* `select2` is not accessible so will remove as soon as I find an accessible alternative.

## Development

If you want to use this plugin, you'll have to run `gulp` to compile the assets and build the production files.

1. Run `npm install` (which will automatically run `composer install`. 
2. Run `gulp build`.
3. Deploy the `my-multi-author-plugin` created inside the directory. It has the files you need.
4. Done.

If you want to develop for the plugin:

1. Create a git branch to work on.
2. Run `npm install` (which will automatically run `composer install`.
3. Run `gulp` every time you make changes.
4. Commit changes.
5. Create a pull request.

*This repo uses Gulp 4. If you have issues updating to version 4, walking through [this guide](https://zzz.buzz/2016/11/19/gulp-4-0-upgrade-guide/) helped me out.*

## Setup

**Right now, the plugin does not have a settings page so, if you'd like to use the multi author functionality on a post type other than "post", you will have to use the "my_multi_author_post_types" filter.**

## Hooks

### Filters

#### 'my_multi_author_post_types'
* Parameter: $post_types (array) - the default is `array( 'post' )`.

*Allows you to filter the post types that use multi author. The only post type set by default is `post`.*

#### 'my_multi_authors'
* Parameter: $authors (array) - the assigned multi author IDs.
* Parameter: $post_id (int) - the post ID.

*Allows you to filter the multi authors for a specific post. Only deals with author IDs. The default is an array of the assigned multi author IDs.*

#### 'my_multi_author_post_author_dropdown_args'
* Parameter: $args (array) - the default arguments.
* Parameter: $post (object) - the post object.

*Allows you to customize the multi author user dropdown in the admin that populates the list of authors to be selected as a multi author. The default is an array of the default arguments.*
