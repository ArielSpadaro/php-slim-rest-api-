<?
$app->post('/products/{id}/image/', function ($request,$response,$args){

    $id = intval( $args['id']);

    $dbClass = new DB(); 
    $db = $dbClass->connect();
    $uploadedFiles = $request->getUploadedFiles();

    $data['id'] = null;
    if(isset($args['id'])) $data['id'] = $args['id']; 

    $inputData = $request->getParsedBody();
  
    if(empty($uploadedFiles))
    {
        $status=409;
        $data = array('message' => 'Image to upload not found', 'status' => $status);
        $payload = json_encode($data);
    
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(409);
    }
    else
    {
        $images= $uploadedFiles['file'];
    
        $payload=array();
    
        foreach($images as $image) 
        {
            $kosher = new kosher();

            $product =$id;
            $file=(uniqid().'_'.$id);
    
            $image_name = $image->getClientFilename();
            $image_type = $image->getClientMediaType();

            $image_type = $kosher->apply(array(
                "field" => $image_type,
                "value" => $image_type,
                "validate" => array("required","is_in_list"=>array("image/jpeg","image/png","image/webp"))
            ));

            if(!empty($kosher->error))
            {
                $data = array('message' => 'Bad Request', 'status' => 400,"error"=>$kosher->error);
        
                array_push($payload, $data);
            }
            else
            {
                $image_format = explode(".", $image_name)[1];
                $fullname = $file.".".$image_format;
                
                $image_path=str_replace(' ', '-', "./files/products/images/".$fullname);
                $image->moveTo($image_path);
        
                $sth = $db->prepare("SELECT  COUNT(*) FROM products_images WHERE product= $product;");
                $sth->execute();
                $result = $sth->fetch(PDO::FETCH_ASSOC);
                $pos = $result['COUNT(*)'];
                $row = [ 'product' => $product, 'file' => $file, 'pos' => $pos ];
                
                $query = " INSERT INTO products_images (product,file,pos) VALUES (:product,:file,:pos+1) ";
                $stmt = $db->prepare($query);
                $stmt->execute($row);
                $last_insert_id = $db->lastInsertId();
            
                $data = array('message' => 'Image of products added', 'status' => 200,"id"=>$last_insert_id);
        
                array_push($payload, $data);
            }
        }
    
        $payload= json_encode($payload, JSON_PRETTY_PRINT );
        
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(201);
    }
})->setName("postProducts_Images");
?>
