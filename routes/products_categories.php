<?
$app->get('/products_categories/[{id}/]', function ($request,$response,$args){
    $dbClass = new DB();
    $db = $dbClass->connect();

    $query = "WHERE 1=1";
    $bind = array();

    $params = $request->getQueryParams();

    if(isset($args['id']))
    {
        $id = intval($args['id']);
        $query .= " AND id = :id ";
        $bind['id'] = $id;
    }else
    {
        if(isset($params['title']))
        {
            $query .= " AND title LIKE :title ";
            $bind["title"] = "%".$params['title']."%";
        }

        if(isset($params['parent'])){
            $query .= " AND parent = :parent ";
            $bind["parent"] = $params['parent'];
        }  

        if(isset($params['deleted'])){
            $query .= " AND deleted = :deleted ";
            $bind["deleted"] = $params['deleted'];
        }  
    }

    $query = "SELECT * FROM products_categories $query";
    $stmt = $db->prepare($query);
    $stmt->execute($bind);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    if(isset($args['id']))  $products = $products[0]; //Hack para devolver un object en vez de un array.

    $response->getBody()->write(json_encode($products));
    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
 
})->setName('getProducts_categories');

$app->post('/products_categories/[{id}/]', function ($request,$response,$args)
{
    $data = $request->getParsedBody();
    $sql="";

    if(array_key_exists("id",$data))
    {
        $id=$data["id"];

        $title=$data["title"];
        $parent=$data["parent"];

        $sql .= "UPDATE products_categories SET title = '$title', parent = $parent WHERE id = $id ";
    }
    else
    {
        $title=$data["title"];
        $parent=$data["parent"];
        $sql .= "INSERT INTO products_categories SET title = '$title', parent = '$parent', deleted = 0";
    }

    $db = new DB();
    $conn = $db->connect();
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    
    $last_insert_id = $conn->lastInsertId();
    
    $data = array('messsge' => 'Product_Brand Added / Modified', 'status' => 200,"id"=>$last_insert_id);
    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
   
})->setName("postProduct_categories");

$app->post('/products_categories/{id}/image[/]', function ($request,$response,$args)
{
    $image = $request->getUploadedFiles();
    if (empty($image['img'])) 
    {
        $status=400;
        $data = array('message' => 'Image to upload not found', 'status' => $status);
        $payload = json_encode($data);
    
        $response->getBody()->write($payload);
        return $response->withStatus($status);
    }

    $id = $args['id'];
    $image= $image["img"];

    if($image->getError() == UPLOAD_ERR_OK)
    {
        $image_name = $image->getClientFilename();
        $image_path=str_replace(' ', '-', "./files/categories/".$image_name);

        $image->moveTo($image_path);

        $sql = "UPDATE products_categories SET image = :image_path WHERE id = :id LIMIT 1";
        
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute(array('image_path'=>$image_path,'id'=>$id));
        $db = null;
        
        $status=200;
        $data = array('path' => $image_path, 'status' => $status);
        $payload = json_encode($data);
    
        $response->getBody()->write($payload);
        return $response->withStatus($status);
    }

})->setName("postProduct_categories_image");

$app->delete('/products_categories/{id}[/]', function ($request,$response,$args)
{
    $id = $args['id'];

    $sql = "UPDATE products_categories SET  deleted = 1 where id = $id";

    $db = new DB();
    $conn = $db->connect();
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    $db = null;
    
    $data = array('message' => 'Categorie deleted', 'status' => 202);
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withStatus(202)->withHeader('Content-Type', 'application/json');
  
})->setName("deleteProducts_categories");
?>