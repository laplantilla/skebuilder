<?php
/**
 * 
 * 
 * @copyright		[YOUNET_COPYRIGHT]
 * @author  		minhTA	
 */

// define('SKEBUILDER_BASE', realpath(SKEBUILDER_SRC_DIR . '/..') . DIRECTORY_SEPARATOR);



require(SKEBUILDER_SRC_DIR . 'handler' . DIRECTORY_SEPARATOR . 'HandlerInterface.php');
require(SKEBUILDER_SRC_DIR . 'helpers.php');
require(SKEBUILDER_SRC_DIR . 'archive.php');
require(SKEBUILDER_SRC_DIR . 'context.php');
require(SKEBUILDER_SRC_DIR . 'skeleton/skeletonCollection.php');
require(SKEBUILDER_SRC_DIR . 'node/NodeAbstract.php');
require(SKEBUILDER_SRC_DIR . 'parser/parser.php');
require(SKEBUILDER_SRC_DIR . 'visitor/visitor.php');
require(SKEBUILDER_SRC_DIR . 'template/TemplateManager.php');
require(SKEBUILDER_SRC_DIR . 'replacement/ReplacementManager.php');
require(SKEBUILDER_SRC_DIR . 'request.php');

Class Skebuilder {

	/**
	 * this variable contains Node object which is used to traverse directory tree and create file, folder
	 *
	 * @var Node 
	 **/
	private $node;

	/**
	 * template manager is in charge of controlling factoring and delivering template object
	 *
	 * @var Object
	 **/
	private $template_manager = null;


	public function getNode()
	{
		return $this->node;
	}

	public function return1() {
		return 1;
	}

	/**
	 * array of error message 
	 *
	 * @var array
	 **/
	static private $error_messages = array();

	public function __construct()
	{

	}
	/**
	 * generate a predefined folder stucture in specificed location
	 * @param  string $module_name name of the module to generate
	 * @param  string $package_type type of module's package: phpfox, SE
	 * @param  string $repository_location where the stucture will be generate 
	 * @return boolean             return true if generate successfully 
	 */
	public function generate($module_name, $package_type, $repository_location = null)
	{
		$skeleton = new Skeleton();
		// get description of the structure
		$structure_description =  $skeleton->getSkeleton($package_type);

		$root_node = new FolderNode($module_name);
		// generate folder and file from the description
		$this->buildNodeCompositeTreeFromStuctureDescription($structure_description, $root_node);

		return $root_node->create($repository_location);
	}

	/**
	 * generate folder and files from the description 
	 * the algorithm to determine path
	 * context variable or 
	 * @param  array $structure_description a recursive array to demonstrate strucutre
	 * @return boolean                        true if creating successfully , false if error occurs
	 */
	public function generateFoldersAndFilesFromStuctureDescription($structure_description)
	{
		//$node can be a file or folder
		//data stucture of a node currenty contains a string
		//but we can refine to have more complex and flexible data structure  
		foreach ($structure_description as $node => $sub_node) {
			if(!is_string($node))
			{
				//this is the situation of no key
				//php will assign number 0,1,2 for each value
				//this is also leaf node
				if(is_int($node))
				{
					$this->node->createLeaf($sub_node);
					continue;
				}
				return false;
			}

			if(!$this->node->createFolder($node)) {
				return false;
			}

			//we go to just created folder
			if(!$this->node->goNext($node)) {
				return false;
			}

			// if sub node is a set of node, we generate them recursively
			if(is_array($sub_node))
			{
				$this->generateFoldersAndFilesFromStuctureDescription($sub_node);
			}
			else if(is_string($sub_node))
			{
				//leaf node
				$this->node->createLeaf($sub_node);
			}

			// we go back to previous cursor
			$this->node->goBack();
		}

		return true;
	}


	/**
	 * root is the point where the tree is generated, if there's no root node, we have no thing to control the tree
	 * 	 
	 */
	public function buildNodeCompositeTreeFromStuctureDescription($structure_description, $root_node)
	{
		foreach ($structure_description as $node => $sub_node) {
			if(!is_string($node))
			{
				//this is the situation of no key
				//php will assign number 0,1,2 for each value
				//this is also leaf node
				if(is_int($node))
				{
					// root is an integer, it means [1] => 'a_string' which will generete (array('a_string 	'))
					// so its sub_node is a leaf node
					$new_node = $this->getLeafNode($sub_node);
					$root_node->add($new_node);

				}
				
			}
			else
			{
				
				$sub_root_node = new FolderNode($node);
				$root_node->add($sub_root_node);
				// if sub node is a set of node, we generate them recursively
				if(is_array($sub_node))
				{
					$this->buildNodeCompositeTreeFromStuctureDescription($sub_node, $sub_root_node);
				}
				else if(is_string($sub_node))
				{
					//leaf node
					$new_node = $this->getLeafNode($sub_node);
					$sub_root_node->add($new_node);
				}
			}
		}

		return $root_node;

	}

	/**
	 * this function will distinct folder and file leafe node base on name
	 * @param  string $name_of_leaf name of the leaf node in tree
	 * @return $new_node               file or folder node
	 */
	public function getLeafNode($name_of_leaf)
	{
		// naive way is to use dot to distinct file node
		if(strstr($name_of_leaf, '.'))
		{
			if(strstr($name_of_leaf, ':'))
			{
				// first element of temp_arry is filename 
				// second element is name of file template
				// filenode second argument receive full path to file template 
				$temp_array = explode(':', $name_of_leaf);
				$new_node = new FileNode($temp_array[0], SKEBUILDER_FILE_TEMPLATE_DIR . $temp_array[1]);
	
			}
			else
			{
				$new_node = new FileNode($name_of_leaf);
			}
			
		}
		else
		{
			$new_node = new FolderNode($name_of_leaf);
		}

		return $new_node;
	}
	

	

	public static function addErrorMessage($sMessage)
	{
		self::$error_messages[] = $sMessage;
	}

	public static function getErrorMessages()
	{
		return implode("\n", self::$error_messages);
	}

	public static function getTemplate($template_name)
	{
		$template_manager = TemplateManager::getInstance(SKEBUILDER_LIB_TEMPLATE);
		return $template_manager->getTemplate($template_name);
	}

	public static function getReplacementOfTemplate($template_name)
	{
		$replacement_manager = ReplacementManager::getInstance(SKEBUILDER_REPLACEMENT_MAPPING_FILE);
		return $replacement_manager->getReplacementOfTemplate($template_name);
	}

	public static function getReplacementByClass($class_name) {
		$replacement_manager = ReplacementManager::getInstance(SKEBUILDER_REPLACEMENT_MAPPING_FILE);
		return $replacement_manager->getReplacementClass($class_name);
	}

	private static $_module_name = 'default';

	public static function setModuleName($module_name) {
		self::$_module_name = $module_name;
	}

	public static function getModuleName() {
		return self::$_module_name;
	}

	private static $_package_id = 'default_package';

	public static function setPackageId($package_id) {
		self::$_package_id = $package_id;
	}

	public static function getPackageId() {
		return self::$_package_id;
	}

	private static $_skeleton_replacement = NULL;

	public static function setSkeletonReplacement($class_name) {
		
		self::$_skeleton_replacement = $class_name;
	}

	public static function getSkeletonReplacement() {
		$replacement_manager = ReplacementManager::getInstance(SKEBUILDER_REPLACEMENT_MAPPING_FILE);
		return $replacement_manager->getReplacementClass(self::$_skeleton_replacement);
	}

	public static function run() {
		if(count($_GET) == 0 && count($_POST) == 0)
		{
			require(SKEBUILDER_BASE . 'view' . DIRECTORY_SEPARATOR . 'homepage.php');
		}
		else
		{

			$package_type = strtolower($_GET['package']);
			$handler = NULL;
			switch ($package_type) {
				case 'phpfox':
					$handler = new PhpfoxHandler;
					break;
				case 'oxwall':
					$handler = new OxwallHandler;
					break;
				default:
					$handler = new PhpfoxHandler;
					break;
			}

			$handler->process();
		}
		
	}
}