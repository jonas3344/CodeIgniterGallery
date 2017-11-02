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
	protected $sTemplate;
  
	public function __construct() {
		$this->CI =& get_instance();
		
		$this->aFolderAttributes = $this->CI->config->item('gallery_attributes');
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
		
		$aTempImages = glob(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/*.jpg');
		$aTemplate = $this->_loadTemplate($sFolder);
		
		$aImages = array_map(function($v) { return basename($v); }, $aTempImages);
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
   * 	name:        getFolders
   *
   * 	returns all folders which are in the gallery folder. Allows to exclude specific folders. sorts by date
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
			return $a['attributes']['date'] + $b['attributes']['date'];
		});
		
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
		
		$config['image_library'] = 'gd2';
		$config['maintain_ratio'] = TRUE;
		$config['width']         = $this->CI->config->item('gallery_thumbnail_with');
		
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
   * 	name:        loadAttributes
   *
   * 	loads the attributes for a gallery out of the file. The attributes are stored in attributes.txt in the gallery folder.
   *	The attributes which should be loaded are stored in $this->config->item('gallery_attributes')
   *
   * 	@param		string	  	$sFolder   		name of the folder
   *
   *	@return		array						array with all the attributes
   **/
	
	public function loadAttributes($sFolder) {
		$this->CI->load->helper('file');
		
		$string = read_file(FCPATH . $this->CI->config->item('gallery_folder') . '/' . $sFolder . '/attributes.txt');
		
		$aLoadedData = explode("\n", $string);
		$aAttributes = $this->CI->config->item('gallery_attributes');
		
		foreach($aLoadedData as $k=>$v) {
			$aAttributes[substr($v, 0, strpos($v, ':'))] = substr($v, strpos($v, ':')+2);
		}
		
		return $aAttributes;
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
		if (file_exists(FCPATH . $this->sGalFolder . '/' . $sFolder . '/_template.html')) {
			$sFile = file_get_contents(FCPATH . $this->sGalFolder . '/' . $sFolder . '/_template.html');
		} else {
			$sFile = file_get_contents(FCPATH . $this->sGalFolder . '/_base_template.html');
		}
		
		if ($sFile === false) {
			throw new Exception("Couldn't find template!", 1);
		}
		
		$aTemplate['sRowBegin'] = substr($sFile, 6, strpos($sFile, '{item}')-6);
		
		$aTemplate['sItem'] = substr($sFile, strpos($sFile, '{item}')+7);
		return $aTemplate;
	}
}