# CodeIgniterGallery
A library for Codeigniter which offers different features around galleries.

Jonas Bay - jonas.bay@bluewin.ch
02.11.2017

-- FILES
Gallery.php => This is the library
gallery.php => This is the configuration-file
_base_template.html => base template
_template.html => a template for each gallery
attributes.txt => a txt-file for each gallery with the attributes specified in the configuration-file

-- INSTALL

1. Put Gallery.php in your library-folder
2. Put gallery.php in your config-folder
3. Don't forget to load the config (either through autoloading or through $this->load->config)
4. Load the library: $this->load->library('gallery', NULL, 'my_gallery');
5. Be sure you set the right path in your config file

-- CONFIGURATION

The following options can be specified in the configuration-file:
gallery_folder => The folder in which all the galleries are stored
gallery_thumbnail_with => the width of the thumbnails which are created by the class
gallery_attributes => an array with all the attributes which are stored in the attributes.txt-files
    Default:    title => title of the gallery
                date => date of the gallery (must be a format which strtotime can convert)
                show => Can be used to switch galleries on and off
                title_image => specifies a title_image which can be used in a gallery-overview

-- PUBLIC FUNCTIONS

createGallery()

$this->my_gallery->createGallery('folder_name')

Creates a gallery out of the photos which are located in the specified folder. Uses the template which is stored in the gallery-folder or the base-template.
Returns an array with the following items:
    sMarkup => html-markup of the gallery
    aAttributes => All attributes of the gallery
                                            
getFolders()

$this->my_gallery->getFolders(array('folderToExclude'))

Returns all folders in the gallery-folder with their attributes. Allows to exclude certain galleries.
Returns an array with all folders. Each folder has the following items:
    folder => name of the folder
    attributes => array with all the attributes
    
createThumbnails()

$this->my_gallery->createThumbnails('folder_name')

Creates thumbnails for the specified folder and stores them in a thumb-folder inside the gallery-folder.


Feel free to add something or post any questions