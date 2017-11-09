<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ----------------------------------------------------------
 * Copyright (c) 2017  Jonas Bay - jonas.bay@bluewin.ch 
 * ----------------------------------------------------------
 *
 *	Gallery creates a gallery out of all images which lie in a certain folder. Uses a template wich is stored in the folder with the filenmane _template.html
 *  Template format: 
 *			{row}: (Format of the row)\n
 *			{item}: (Format of the item which should include {full_image} and {thumbnail}
 *			If there is no specified template fo the gallery there is a _base_template.html in /gallery which is taken
 *
 *
 * 	@author		  	Jonas Bay
 *
 *
 */
 
class Gallery {
	
	protected $CI;
  
	public function __construct() {
		$this->CI =& get_instance();
	}
	
	/**
   * 	name:        createGallery
   *
   * 	returns html/css-markup for a gallery
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   *	@return		array						array with items in $this->config->item('gallery_attributes')
   **/
	
	public function createGallery($sFolder) {
		$sMarkup = '';
		
		$aImages = glob(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/*.jpg');
		$aTemplate = $this->_loadTemplate($sFolder);
		
		$aImages = array_map(function($v) { return basename($v); }, $aImages);
		$aAttributes = $this->loadAttributes($sFolder);
		
		$sMarkup .= $aTemplate['sRowBegin'];
		foreach($aImages as $k=>$v) {
			$sTemp = str_replace('{full_image}', base_url() . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/' . $v, $aTemplate['sItem']);
			$sTemp = str_replace('{thumbnail}', base_url() . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/thumb/' . $v, $sTemp);
			$sMarkup .= $sTemp;
		}
		$sMarkup .= '</div>';
		
		return array('sMarkup' => $sMarkup, 'aAttributes' => $aAttributes);
	}
	
   /**
   * 	name:        getImages
   *
   * 	returns an array with all images inside of a folder
   *
   * 	@param		string	  	$sFolder  		string with the name of the folder
   *
   *	@return		array						array with all the images
   **/
	
	public function getImages($sFolder) {
		$aImages = glob(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/*.jpg');
		return array_map(function($v) { return basename($v); }, $aImages);
	}
	
   /**
   * 	name:        getFolders
   *
   * 	returns all folders which are in the gallery folder. Allows to exclude specific folders. sorts by date ASC
   *
   * 	@param		array	  	$aExclude  		array with names of folders which shouldnt be included in the list.
   *
   *	@return		array						array with folder names and attributes
   **/
	
	public function getFolders($aExclude = array()) {
		
		$aFolders = glob(FCPATH . $this->CI->config->item('gallery_folder') . "/*", GLOB_ONLYDIR);
		
		$aTemp = array_map(function($v) { return basename($v); }, $aFolders);
		$aFolders = array();
		
		foreach($aTemp as $k=>$v) {
			if (!in_array($v, $aExclude)) {
				$aFolders[$k]['attributes'] = $this->loadAttributes($v);
				$aFolders[$k]['attributes']['date'] = strtotime($aFolders[$k]['attributes']['date']);
				$aFolders[$k]['folder'] = $v;
			}			
		}
				
		usort($aFolders, function($a, $b) {
			return $a['attributes']['date'] - $b['attributes']['date'];
		});
		
		$aFolders = array_reverse($aFolders);
		
		return $aFolders;
	}
	
   /**
   * 	name:        createThumbnails
   *
   * 	creates thumbnails of a certain folder and stores them unter /thumb
   *
   * 	@param		array	  	$sFolder  		name of the folder
   *
   *	@return
   **/
	
	public function createThumbnails($sFolder) {
		$aFiles = glob(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/*.jpg');
		
		$config['image_library'] 	= 'gd2';
		$config['maintain_ratio'] 	= FALSE;
		$config['master_dim'] 		= 'height';
		$config['width']        	= $this->CI->config->item('gallery_thumbnail_width');
		$config['height']			= $this->CI->config->item('gallery_thumbnail_height');
		
		$this->CI->load->library('image_lib');
		
		if (!is_dir(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/thumb')) {
			mkdir(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/thumb');
		}
		
		foreach($aFiles as $k=>$v) {
			$config['source_image'] = $v;
			$config['new_image'] = FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/thumb/' . basename($v);
			
			$this->CI->image_lib->initialize($config);
			$this->CI->image_lib->resize();
		}
	}
	
   /**
   * 	name:        writeAttributes
   *
   * 	Replaces the attributes.txt inside of a folder with the attributes in the parameter. If the file does not exist it creates the file
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *	@param		array		$aAttributes	array with the attributes
   *
   **/
	
	public function writeAttributes($sFolder, $aAttributes) {
		$this->CI->load->helper('file');
		$sData = '';
		
		foreach($aAttributes as $k=>$v) {
			$sData .= $k . ': ' .$v . "\n";
		}
		
		if (!write_file(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/attributes.txt', $sData)) {
			throw new Exception('Couldn not write atrribute-file!', 2);
		}

	}
	
   /**
   * 	name:        loadAttributes
   *
   * 	loads the attributes for a gallery out of the file. The attributes are stored in attributes.txt in the gallery folder.
   *	The attributes which should be loaded are stored in $this->config->item('gallery_attributes'). If the attributes.txt file does not exist
   *	the function creates it.
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   *	@return		array						array with all the attributes
   **/
	
	public function loadAttributes($sFolder) {
		$this->CI->load->helper('file');
		
		$string = file_get_contents(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/attributes.txt');
		
		if ($string === FALSE) {
			// file doesn't exist
			$this->writeAttributes($sFolder, $this->CI->config->item('gallery_attributes'));
		}
		
		$aLoadedData = explode("\n", $string);
		$aAttributes = $this->CI->config->item('gallery_attributes');
		
		foreach($aLoadedData as $k=>$v) {
			$aAttributes[substr($v, 0, strpos($v, ':'))] = substr($v, strpos($v, ':')+2);
		}
		
		return $aAttributes;
	}
	
	/**
   * 	name:        deleteGallery
   *
   * 	Deletes a gallery from the file-system. Deletes the whole folder including images, attributes.txt and _template.html (if available).
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   **/
	
	public function deleteGallery($sFolder) {
		$this->CI->load->helper('file');
		
		delete_files(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder, true);
		rmdir(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder);
	}
	
   /**
   * 	name:        backupGallery
   *
   * 	Puts all files inside a gallery (photos, attributes.txt and _template.html) into a zip and forces the browser to download it
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   **/
	
	public function backupGallery($sFolder) {
		
	}
	
   /**
   * 	name:        loadTemplate
   *
   * 	loads a template for a gallery. Looks for _template.html in the folder of the gallery. If it doesn't exist it takes _base_gallery.html
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   *	@return		array						array with sRowBegin (start-div) and sItem (div of the item)
   **/
	
	private function _loadTemplate($sFolder) {
		if (file_exists(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/_template.html')) {
			$sFile = file_get_contents(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/_template.html');
		} else {
			$sFile = file_get_contents(FCPATH . $this->CI->config->item('gallery_folder') . '/_base_template.html');
		}
		
		if ($sFile === false) {
			throw new Exception('Couldn not find template!', 1);
		}
		
		$aTemplate['sRowBegin'] = substr($sFile, 6, strpos($sFile, '{item}')-6);
		
		$aTemplate['sItem'] = substr($sFile, strpos($sFile, '{item}')+7);
		return $aTemplate;
	}
}