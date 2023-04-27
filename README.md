# Crocodoc integration for ExpressionEngine 2

This add-on will automatically send files (of selected types and in selected upload locations) that you upload in ExpressionEngine to Crocodoc.com.

↑Extension settings
In order to have uploaded files automatically posted to your Crocodoc account, you’ll need to enter API token, which you can find on Dashboard.

Select upload locations that will contain the files you want to be sent to Crocodoc. If none is selected, nothing is sent.

You can also restrict Crocodoc upload to certain MIME types of files. Leaving the field blank would result in sending all files in checked directories (upload locations). Example setting: application/pdf, application/x-pdf, application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint

Also provide server path and URL to directory where cached thumbnail files will be saved. The script will create directory if it does not exist.

↑Template tags
↑Viewing URL

{exp:crocodocee:url file=”{assets:server_path}”}


{exp:crocodocee:url file=”{attachment}{file_id}{/attachment}”}

Returns URL to converted document, intended to be used as ‘src’ parameter of iframe tag.

Parameters:

file — file id OR file path on server. Required, unless entry_id is specified.
entry_id — entry_id to fetch files for. Required, unless file is specified.
field_id — ID of field_id that contains the file. If omited, will return 1 last file from entry.
field — field name, to be used instead of field_id
editable=“yes” — Allows users to create annotations and comments while viewing the document
user — A user ID and name joined with a comma (e.g.: 1337,Peter). Required if editable is true
filter — Limits which users’ annotations and comments are shown. (“all”, “none”, or a comma-separated list of user IDs)
admin=“yes” — Allows the user to modify or delete any annotations and comments; including those belonging to other users.
downloadable=“yes” — Allows the user to download the original document
copyprotected=“yes” — Prevents document text selection
demo=“yes” — Prevents document changes such as creating, editing, or deleting annotations from being persisted
sidebar — Sets whether the viewer sidebar (used for listing annotations) is included. (“none”, “auto”, “collapse”, “visible”)
Check this page for more information about parameters.

↑Thumbnail

{exp:crocodocee:thumb file=”{assets:server_path}”}


{exp:crocodocee:thumb file=”{attachment}{file_id}{/attachment}”}

Returns URL of thumbnail of converted document (saved as png image in folder you specified in settings). The thumbnail is cached forever, so if for some reason you want to regenerate it, you’ll need to delete locally hosted image.

Parameters:

size — Maximum dimensions of the thumbnail in the format {width}x{height}. Largest dimensions allowed are 300x300. Default: 100x100
Check this page for more information about parameters.

↑UUID

{exp:crocodocee:uuid file=”{assets:server_path}”}


{exp:crocodocee:uuid file=”{attachment}{file_id}{/attachment}”}

Returns only UUID of converted document.

Parameters:

file — file id OR file path on server. Required.

