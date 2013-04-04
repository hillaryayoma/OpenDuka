<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Projects extends CI_Controller {

    
       
       function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form','url'));
		$this->load->database();
		//$this->load->library('jquery');
		$this->load->model('project');
	}

	function index()
	{
		$this->load->view('project', array('error' => ''));
	}

	function do_retrieve()
	{
			$params = array(
               		'http'=>array(
	               		'username' => 'username',
	               		'password' => 'password'
	               		)
				); 
			$context = stream_context_create($params);

			// Open the file using the HTTP headers set above
			$file = $this->processCommand('https://www.documentcloud.org/api/projects.json');
			
			$projects = json_decode($file,true);
			echo count($projects['projects'][0]['document_ids']);
			$content = $this->array_extract($projects['projects'][0]['document_ids']);
			/*
			$entities = $this->processCommand('https://www.documentcloud.org/api/documents/'.$projects['projects'][0]['document_ids'][0].'/entities.json');
echo $entities;
	$representation = $this->processCommand('https://www.documentcloud.org/api/documents/'.$projects['projects'][0]['document_ids'][0].'.json');
echo $representation;*/
			$content = array('content' => $content, 'error' => $file['error'], 'filename' => '');
			$data_head = array('page_title' => 'Project list!');

			$this->load->view('header',$data_head);
			$this->load->view('project',$content);
			$this->load->view('footer');
			//	$this->load->view('upload_success', $data);
	}
	
	function processCommand($url, $method="GET", $headerType="XML", $src="") {

        $method = strtoupper($method);
        $headerType = strtoupper($headerType);
        $session = curl_init();
        curl_setopt($session, CURLOPT_USERPWD, "benjamin@openinstitute.com:private123456"); 
        curl_setopt($session, CURLOPT_URL, $url);
        if ($method == "GET") {
            curl_setopt($session, CURLOPT_HTTPGET, 1);
        } else {
            curl_setopt($session, CURLOPT_POST, 1);
            curl_setopt($session, CURLOPT_POSTFIELDS, $src);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($session, CURLOPT_HEADER, false);
        if ($headerType == "XML") {
            curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
        } else {
            curl_setopt($session, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        }
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        if (preg_match("/^(https)/i", $url))
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($session);
        curl_close($session);

        return $result;
    }
    

	function array_extract($arr){
		$str ='';
		foreach ($arr as $key => $val){
			$str.= '<b>'.$key .':</b>';
			if(is_array($val)){
				$str.= $this->array_extract($val);
			}else{
				$str.= $val.'<br/>';
				$entities= $this->processCommand('https://www.documentcloud.org/api/documents/'.$val.'/entities.json').'<br/>	';//entities
				$representation=$this->processCommand('https://www.documentcloud.org/api/documents/'.$val.'.json').'<br/>'; //representation
				
				$result = json_decode($representation, true);
				$data = array(
				'doc_id'=>$result['id'],
				'title'=>$result['title'],
				'pages'=>$result['pages'],
				//'description' => $result['description'],
				//'source' => $result['source'],
				'created_at' => $result['created_at'],
				'updated_at' => $result['updated_at'],
				'canonical_url' => $result['canonical_url'],
				'contributor' => $result['contributor'],
				'contributor_organization' => $result['contributor_organization'],
				'pdf' => $result['resources']['pdf'],
				'text' => $result['resources']['text'],
				'thumbnail' => $result['resources']['thumbnail'],
				'search' => $result['resources']['search'],
				'pagetext' => $result['resources']['page']['text'],
				'pageimage' => $result['resources']['page']['image'],
				'entities' => $entities,
				'representation' => $representation 
				);
				
				$str.= $this->project->insert_document($data);

	 		}
	 	}
	 	return $str;
	 }
	
	function entity()
	{
		
		$context = $_POST['content'];	
		$File_Name = $_POST['filename'];		
		//echo $context;exit;
		$doc_data = array('DocName' => $File_Name,'DocText' => $context );
		$DocID = $this->post->insert_document($doc_data);
		
		
		$apikey = "sp3u4wvyqbpx34zauxqp7qr2";
		$oc = new OpenCalais($apikey);
		
		$entities = $oc->getEntities($context);
		$entity_info = "";
		$entity_type_db = "";
		$entity_val_db = "";
		//$tree .= $File_Name;

		foreach ($entities as $type => $values) {
			
			$entity_type_db .= $type .",";

			foreach ($values as $entity) {
				$this->post->insert_entity($type,$entity,$DocID);
			}
			
		}

		$entity_data = array('DocType' => $entity_type_db, 'DocID' => $DocID);
		$this->post->update_document($entity_data);
		
		$entity_type_db = "";
		
		redirect('<?php echo base_url() . index_page();?>/trees/index/'.$DocID);
		/*
		$content=array('entities' => $entity_info,'filename' => $File_Name,'tree' => $tree,'error' => '');
		$data_head = array('page_title'     => 'Entity Extraction');


		$this->load->view('header_entity',$data_head);
		$this->load->view('entity',$content);
		$this->load->view('footer');
		*/
	}
	
	
			
}

/* End of file posts.php */
/* Location: ./application/controllers/posts.php */
