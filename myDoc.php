<?php
/* @author CaDJoU <charles@kwa.digital>
 * @version Alpha
 * @example php myDoc.php [path PHP Code] [Path To make the Doc]
 * @copyright MIT License
 *
 * Make quickly a Class PHP Documentation
 */


class myDoc
{
	protected $iterator;
	
	protected $classes;
	
	protected $path_lib;
	
	protected $path_doc;
	
	protected $do_one_file = false;
	
	protected $filters = ['\.(?!html$|htm$|css$|js$|md$|map$|scss$)\w{0,}$'];
	
	public function __construct($path_lib,$path_doc)
	{
		if (!is_dir($path_lib) and !is_file($path_lib)) die('Error path');
		
		$this->path_lib = $path_lib;
		$this->path_doc = $path_doc; // TODO: Check and validation for $path_doc
		
		$this->iterator = $this->getScandir($path_lib);
		foreach ($this->iterator as $key=>$info) {
			$end_path = substr($info->getPathname(),strlen($path_lib)+1); // prendre le dirname si fichier
			if (!in_array($info->getFilename(),['.','..']) and $this->isFilterPass($end_path)){
				$file_content = file_get_contents($info->getPathname());
				// print_r($file_content);
				$this->addClass($this->info($this->content($file_content),$end_path));
				
				// echo $end_path . "\n";
			}
		}
	}
	
	public function addFilter($patterns){
		if (is_string($patterns)){
			$patterns = [$patterns];
		}
		foreach($patterns as $pattern){
			if (is_string($pattern)){
				$this->filters[] = $pattern;
			}
		}
	}
	
	public function getClasses()
	{
		return $this->classes;
	}
	
	public function makeHtml()
	{
	    if (!is_dir($this->path_doc)){
            mkdir($this->path_doc,'0755',true);
        }
        if (!is_dir($this->path_doc . '/doc')){
            mkdir($this->path_doc . '/doc','0755',true);
        }
        copy(__DIR__ . '/resources/css.css',$this->path_doc . '/css.css');
        copy(__DIR__ . '/resources/js.js',$this->path_doc . '/js.js');

		$html = [];
		foreach($this->classes as $data)
		{
			//data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
			$html[$data['name']] = '
							<li class="nav-item">
								<a class="nav-link doc" href="doc/' . $data['name'] . '.html" target="showContent">
									<span data-feather="clipboard"></span>
									' . $data['name'] . '
								</a>
							</li>';
			$page = '<pre>' . var_export($data,true) . '/</pre>';
			file_put_contents($this->path_doc . '/doc/' . $data['name'] . '.html',$page);
		}
		ksort($html);
		$content = str_replace('%%menu%%',implode("\n",$html),file_get_contents(__DIR__ . '/resources/page.html'));
		file_put_contents($this->path_doc . '/index.html',$content);
	}
	
	protected function isFilterPass($path){
		if ($this->do_one_file) return true;
		foreach($this->filters as $pattern){
			preg_match_all("/$pattern/", $path,$matches);
			$re = '/\.(?!html$|htm$|css$|js$|md$)\w{0,}$/';
			preg_match($re, $path, $matches, PREG_OFFSET_CAPTURE, 0);
			if (!$matches) return false;
		}
		return true;
	}
	
	protected function addClass($class)
	{
		foreach($class as $k=>$v)
		{
			if (is_array($v))
			{
				if (isset($this->class[$k])) echo "Class en double\n";
				$this->classes[$k] = $v;
			}
		}
	}
	
	protected function getScandir($path)
	{
		if (is_dir($path)) return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

		if (is_file($path)){
			$ob = new SplFileObject($path);
			$this->do_one_file = true;
			// print_r($ob);
			return [$ob];
		}
		return [];
	}
	
	protected function content($text){
		$re = '/([^\{\}])\{([^\{\}]*)\}/m';
		$tm = $text;
		$i = 1;
		$g = [];
		while(true){
			preg_match_all($re, $tm, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, 0);
			krsort($matches);
			if (!$matches) break;
			foreach($matches as $v)
			{
				$d =  $v[0][1];
				$f =  $v[0][1] + strlen($v[0][0]);
				$g[$i] = $v[0][0];
				$t = [];
				$t[] = substr($tm,0,$d);
				$t[] = '_' . $i . '_';
				$t[] = substr($tm,$f);
				$tm = implode('',$t);
				$i++;
			}
		}
		$r['text'] = str_replace("\r","",$tm);
		$r['part'] = $g;
		// print_r($r);
		return $r;
		
	}
	
	protected function info($info,$path)
	{
		if (!isset($info['text'],$info['part'])) return false;
		
		$regex_class = '/\W[^:](class)\W(.*)\W?_(\d+)_/m';
		$regex_class_param = '/(\w*)\W?/m';
		$regex_method_param = '/\W?((?:public|private|protected)?)\W?(function)\W+(\w*)\((.*)\)/m';
		$regex_property_param = '/((?:public|private|protected))\W?(\w{0,})\W?(\$\w*)/m';
		$text = $info['text'];

		preg_match_all($regex_class, $text, $m_class, PREG_SET_ORDER, 0);
		$class_info = [];
		foreach($m_class as $class)
		{
			preg_match_all($regex_class_param, $class[2], $m_class_param, PREG_SET_ORDER, 0);
			$type = '';
			$class_name = '';
			$groupe = $class[3];
			foreach($m_class_param as $k=>$params)
			{
				$param = trim($params[0]);
				$param = trim($param,'"\'');
				if ($k==0 and !empty($param))
				{
					$class_name = $param;
					$class_info[$class_name]['name'] = $class_name;
					$class_info[$class_name]['path'] = $path;
					continue;
				}
				if ($param == 'extends' and $class_name)
				{
					$type = 'extends';
					continue;
				}
				if ($type and $class_name)
				{
					$class_info[$class_name][$type] = $param;
					$type = '';
					continue;
				}
			}
			
			if ($class_name and isset($info['part'][$groupe]))
			{
				preg_match_all($regex_property_param, $info['part'][$groupe], $m_property_param, PREG_SET_ORDER, 0);
				foreach($m_property_param as $property_params)
				{
					// print_r($property_params);
					$property_params = array_map('trim',$property_params);
					list(,$type,$typage,$name) = $property_params;
					// $type = empty($type) ? 'public' : $type;
					$property['name'] = $name;
					$property['type'] = $type;
					$property['typage'] = $typage;
					$class_info[$class_name]['property'][] = $property;
				}
				
				
				
				preg_match_all($regex_method_param, $info['part'][$groupe], $m_method_param, PREG_SET_ORDER, 0);
				// print_r($m_method_param);
				foreach($m_method_param as $method_params)
				{
					$method_params = array_map('trim',$method_params);
					list(,$type,,$name,$params) = $method_params;
					$type = empty($type) ? 'public' : $type;
					$method['name'] = $name;
					$method['type'] = $type;
					$method['params'] = $params;
					$class_info[$class_name]['method'][] = $method;
				}
			}
		}
		
		return $class_info;
	}
}
print_r($argv);
$path_lib = isset($argv[1]) ? $argv[1] : '';
$path_doc = isset($argv[2]) ? $argv[2] : __DIR__ . '/myDoc-' . date('Y-m-d-H-i-s');
echo "$path_lib\n$path_doc\n";
if (!$path_lib or (!is_file($path_lib) and !is_dir($path_lib)))
{
	echo 'Error : the path of the PHP lib is not a file or directory' . "\n";
	die();
}
//v$path = 'D:\sites\nextcloud';
// $path = 'D:\sites\ispconfig3_install\server\lib\classes\functions.inc.php';
// $path = 'D:\sites\nextcloud\lib\public\Accounts';

$myDoc = new myDoc($path_lib,$path_doc);
foreach($myDoc->getClasses() as $key=>$data){
	echo "$key -> {$data['path']}\n";
}

$myDoc->makeHtml();
// TODO : Check error PHP Warning:  file_put_contents(doc/DefaultExternalServices_3131_\.html): failed to open stream: No such file or directory in D:\sites\phpDocumentor\myDoc.php on line 72
// TODO : Check error PHP Warning:  file_put_contents(doc/SemVer_1153_\.html): failed to open stream: No such file or directory in D:\sites\phpDocumentor\myDoc.php on line 72


