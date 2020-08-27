<?php
/*
	Helper functions for Corporate Documents plugin.
*/

/*
	Return a font awesome class appropriate for the passed-in mime type
*/
function get_fa_icon_class($mime_type) {
  switch ($mime_type) {
    case 'application/pdf':
      return 'fa-file-pdf'; break;
    case 'image/jpeg':
    case 'image/png':
    case 'image/gif':
      return 'fa-file-image'; break;
    case 'video/mpeg':
    case 'video/mp4': 
    case 'video/quicktime':
      return 'fa-file-video'; break;
    case 'text/csv':
      return 'fa-file-csv'; break;
    case 'text/plain': 
      return 'fa-file'; break;
    case 'text/xml':
    case 'text/html':
      return 'fa-file-code'; break;
    default:
      return 'fa-file-alt'; break;
  }
}

function cdox_allow_upload_xml($mimes) {
  $mimes = array_merge($mimes, array('xml' => 'application/xml'));
  return $mimes;
}
