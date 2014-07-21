Braindump REST API
==================

Braindump is an alternative to [Evernote](http://www.evernote.com), born out of 
frustration as it is becoming more more locked down.
As I want to be in control of my own notes and bookmarks, I've
decided to take a shot at developing my own note takeing app.

This project contains the backend and API part of the project. 
It aims to provide a simple REST-style API to to store and retrieve 
notes. These notes will be stored in a server side database.

Created by Wilco Menge (wilcomenge@gmail.com), the code is hosted at [github](https://github.com/wmenge/braindump-api) for the REST based backend API.

See [Braindump Client](https://github.com/wmenge/braindump-client) for a HTML/Javascript based client.

Roadmap
-------
Currently, storing and retrieving simple HTML notes is supported. If this project works out well I'll be adding functionality so Braindump can serve as a reasonable alternative to Evernote.

**Currently supported features:**

* Maintaining/retrieving Notebooks: A notebook contains a number of Notes
* Maintaining/retrieving Notes: A note is a piece of plaintext or HTML contained in a Notebook

**Planned features:**

* Simple admin console embedded in this project
* Tagging of notes
* Import/export of notes
* Multiple users
* Search
* Paste images/attachments
* Security (Encryption of notes)

Implementation details
----------------------

* PHP 5.3.3 (or higher) is required
* [Composer](https://getcomposer.org) for dependancy management
* [SQLite](http://www.sqlite.org)
* [Slim PHP Framework](http://www.slimframework.com)
* [Idiorm](http://j4mie.github.io/idiormandparis/) for object/relational mapping

###Data Model

####Notebook

field       |type
---         |---
**id**      |`id`
title   |`string`

####Note

field       |type
---         |---
**id**      |`id`
notebook_id |`foreign_key`
title   |`string`
created   |`timestamp`
updated   |`timestamp`
url |`url`
type | 'HTML' or 'Text'
content   |`string`

Future versions:

####Tag

field       |type
---         |---
**tag_id**  |`id`
note_id   |`foreign_key`
tag     |`string`


API Usage
=========

**Note:** For a procedure for moving a note from one notebook to another, see the F.A.Q.

API Summary
-----------

Url | Operation | Description
--- | ------ | ---
/   | `GET`    |Alias of /notebooks
/notebooks | `GET` | Retrieves list of available Notebooks
/notebooks/`:id` | `GET`| Retrieves details of Notebook with given `:id`
/notebooks |`POST`|Creates a new notebook
/notebooks/`:id` |`PUT`|Updates notebook with given `:id`
/notebooks/`:id` |`DELETE`|Deletes notebook with given `:id`
/notes?q=`:query` |`GET`|Retrieve an optionally filtered list of all available notes
/notebooks/`:id`/notes?q=`:query` |`GET`|Retrieve an optionally filtered list of notes in Notebook with `:id`    (without content)
/notebooks/`:notebook_id`/notes/`:note_id` |`GET`|Get details of note with `:note_id` (with content)
/notes/`:note_id` |`GET`|Alias of /notebooks/:notebook_id/notes/:note_id
/notebooks/`:id`/notes |`POST`|Creates a new note
/notebooks/`:notebook_id`/notes/`:note_id` |`PUT`|Updates the given note and makes sure it is contained in the notebook with id :id
/notebooks/`:id`/notes/`:note_id` |`DELETE`|Deletes the note with `:note_id`
/notes/:note_id |`DELETE`|Alias of /notebooks/:id/notes/:note_id



API Details
-----------

### / `GET`
Alias of /notebooks

---
### /notebooks `GET`

  Retrieves a list of available Notebooks.

  Use query parameter `sort` to specify a list of fields to sort by:
  `/notebooks?sort=title,-id` means: sort by `title`, then by `id` descending.


Example Response:
````
[
    {
        "id": 1,
        "title": "Test Notebook",
        "noteCount": 1
    },
    {
        "id": 2,
        "title": "Another Notebook",
        "noteCount": 1
    },
    {
        "id": 3,
        "title": "Empty Notebook",
        "noteCount": 0
    }
]
````

---
### /notebooks/:id `GET`

  Retrieves details of Notebook with given :id

Example Response:
````
{
    "id": 1,
    "title": "Test Notebook",
    "noteCount": 1
}
````

---
### /notebooks `POST`
  
  Creates a new notebook

Example Request:

HTTP Request Headers:
`Content-Type: application/json`
````
{
    "title": "New Notebook"
}
````
Example Response:
````
{
    "id": 1,
    "title": "New Notebook",
    "noteCount": 0
}
````

---
###  /notebooks/:id `PUT`

Updates notebook with given :id

Example Request:

HTTP Request Headers:
`Content-Type: application/json`
````
{
    "id": 1,
    "title": "Modified title"
}
````
Example Response:
````
{
    "id": 1,
    "title": "Modified title",
    "noteCount": 0
}
````

---
### /notebooks/:id `DELETE`

  Deletes notebook with given :id, also deletes all notes
  contained in this notebook

---
### /notes?q=:query `GET`

Retrieve list of all available notes. If `:query` is supplied, only results
with matching title or content are returned.

Use query parameter `sort` to specify a list of fields to sort by:
`/notebooks?sort=title,-id` means: sort by `title`, then by `id` descending.

---
### /notebooks/:id/notes?q=:query `GET`

Retrieves a list of Notes contained in given notebook, without the
actual content of the notes. If :query is supplied, only results
with matching title or content are returned.

Use query parameter `sort` to specify a list of fields to sort by:
`/notebooks?sort=title,-id` means: sort by `title`, then by `id` descending.

Example Response:
````
[
    {
        "id": 1,
        "notebook_id": 1,
        "title": "Test Note from SQLite",
        "created": 1398979024,
        "updated": 1398979024,
        "url": "http:\/\/www.google.com",
        "type": "Text",
        "content": "This is a note"
    },
    {
        "id": 2,
        "notebook_id": 1,
        "title": "Another test note",
        "created": 1398979024,
        "updated": 1398979024,
        "url": "http:\/\/www.google.com",
        "type": "Text",
        "content": "This is a note"
    }
]
````

---
### /notebooks/:notebook_id/notes/:note_id `GET`

  Retrieves details of Note with given :note_id
  (contained in Notebook with :notebook_id).
  Includes the content of the note.
    
  **Note:** redirects to url with correct notebook_id if the note
  exists in a different notebook.

Example Response:
````
{
    "id": 1,
    "notebook_id": 1,
    "title": "Test Note from SQLite",
    "created": 1398979024,
    "updated": 1398979024,
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is a note"
}
````

---
### /notes/:note_id `GET`

  Retrieves details of Note with given :note_id
  
  Alias of /notebooks/:notebook_id/notes/:note_id but without the
  check on :notebook_id.

---
### /notebooks/:id/notes `POST`

  Creates a new note in given notebook.
    
  Example Request:

HTTP Request Headers:
`Content-Type: application/json`
````
{
    "title": "Test Note from SQLite",
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is a note"
}
````
Example Response:
````
{
    "id": 1,
    "notebook_id": 1,
    "title": "Test Note from SQLite",
    "created": 1398979024,
    "updated": 1398979024,
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is a note"}
}
````

---
### /notebooks/:notebook_id/notes/:note_id `PUT`

  Updates note in notebook with given :note_id
  (contained in Notebook with :notebook_id)

  **Note:** redirects to url with correct notebook_id if the note
  exists in a different notebook.

  Example Request:

HTTP Request Headers:
`Content-Type: application/json`
````
{
    "title": "Modified Title",
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is some modified content"
}
````
Example Response:
````
{
    "id": 1,
    "notebook_id": 1,
    "title": "Modified Title",
    "created": 1398979024,
    "updated": 1398979034,
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is some modified content"}
}
````

---
### /notebooks/:id/notes/:note_id `DELETE`

  Deletes note with given :note_id
  (contained in Notebook with :notebook_id)

  **Note:** redirects to url with correct notebook_id if the note
  exists in a different notebook.

---
### /notes/:note_id `DELETE`

  Deletes note with given :note_id

  Alias of /notebooks/:notebook_id/notes/:note_id but without the
  check on :notebook_i)
  
F.A.Q
-----

###How do move a note from one notebook to another?

Execute a `PUT` Request to the note with the target notebook id in the url

Example:

Consider the following note, contained in the notebook with id  `1`:
````
{
    "id": 1,
    "notebook_id": 1,
    "title": "Test Note from SQLite",
    "created": 0,
    "updated": 0,
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is a note"
}
````

To move it to the notebook with id `2`, Execute the following Request:

`/notebooks/2/notes/1` `PUT`

HTTP Request Headers:
`Content-Type: application/json`

````
{
    "id": 1,
    "notebook_id": 1,
    "title": "Test Note from SQLite",
    "created": 0,
    "updated": 0,
    "url": "http:\/\/www.google.com",
    "type": "Text",
    "content": "This is a note"
}
````

Todo
----

* Exand README into:

README
FAQ
INSTALL
CHANGE_LOG (for every release)

Links & References
------------------

* Parsing large xml documents: http://stackoverflow.com/questions/911663/parsing-huge-xml-files-in-php
  (for evernote import)
* http://dev.evernote.com/doc/articles/enml.php for evenrote html format
* https://phpbestpractices.org
* http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api
