# My Multi Author Plugin

WordPress plugin that allows you to attribute multiple authors to single post.

## Setup

**Right now, the plugin does not have a settings page so, if you'd like to use the multi author functionality on a post type other than "post", you will have to use the "my_multi_author_post_types" filter.**

## Hooks

### Filters

#### 'my_multi_author_post_types'
Parameter: $post_types, array, default: array( 'post' ).

*Allows you to filter the post types that use multi author. 'post' is the default.*

#### 'my_multi_authors'
Parameter: $authors, array, default: array of the assigned multi author IDs.
Parameter: $post_id, int, default: the post ID.

*Allows you to filter the multi authors for a specific post. Only deals with author IDs.*

#### 'my_multi_author_post_author_dropdown_args'
Parameter: $args, array, default: the default arguments
Parameter: $post, object, default: the post object.

*Allows you to customize the multi author user dropdown in the admin that populates the list of authors to be selected as a multi author.*