<?
use Slim\Http\UploadedFile;

$app->get('/products_brands/[{id}/]', function ($request,$response,$args){
    $dbClass = new DB(); //DB connection
    $db = $dbClass->connect();

    $query = "WHERE 1=1";
    $bind = array();

    $params = $request->getQueryParams();

    if(isset($args['id'])){
        $id = intval($args['id']);
        $query .= " AND id = :id ";
        $bind['id'] = $id;
    }else{
        if(isset($params['title'])){
            $query .= " AND title LIKE :title ";
            $bind["title"] = "%".$params['title']."%";
        }

        if(isset($params['pub'])){
            $query .= " AND pub = :pub ";
            $bind["pub"] = $params['pub'];
        }
        
        if(isset($params['deleted'])){
            $query .= " AND deleted = :deleted ";
            $bind["deleted"] = $params['deleted'];
        }
    }

    $query = "SELECT * FROM products_brands $query";
    $stmt = $db->prepare($query);
    $stmt->execute($bind);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    if(isset($args['id']))  $products = $products[0]; //Hack para devolver un object en vez de un array.

    $response->getBody()->write(json_encode($products));
    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
 
})->setName('getProducts_brand');

$app->post('/products_brands/[{id}/]', function ($request,$response,$args){

    $dbClass = new DB(); //DB connection
    $db = $dbClass->connect();


    $data = $request->getParsedBody();

    $data['id'] = null;
    if(isset($args['id'])) $data['id'] = $args['id']; 

  
    //LIMPIAR Y VALIDAR DATOS (CLASE DEL MIERCOLES)


    $query = "INSERT INTO products_brands
    SET  
    id = :id,
    title = :title,
    pub = 0,
    deleted = 0
    ON DUPLICATE KEY 
    UPDATE
    id = :id, 
    title = :title,
    pub = 0,
    deleted = 0
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($data);
    $last_insert_id = $db->lastInsertId();
    
    $data = array('messsge' => 'Product_Brand Added / Modified', 'status' => 200,"id"=>$last_insert_id);
    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');


})->setName("postProduct_Brand");

$app->post('/products_brands/{id}/pub', function ($request,$response,$args)
{
    $id = $args['id'];
    $sql = "UPDATE products_brands SET  pub = 1 where id = $id";
    try 
    {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        $db = null;
        $data = array('msg' => 'Producto puesto en PUB', 'status' => 202);
        $payload = json_encode($data);
        

        $response->getBody()->write($payload);
        return $response->withStatus(202)->withHeader('Content-Type', 'application/json');
    } 
    catch(PDOException $e) 
    {
        $error = array("message"=> $e->getMessage());

        $response->getBody()->write(json_encode($error));
        return $response;        
    }
})->setName("postProducts_brand_pub");

$app->delete('/products_brands/{id}', function ($request,$response,$args){
    $id = $args['id'];
    $dbClass = new DB(); //DB connection
    $db = $dbClass->connect();
    $query = "UPDATE products_brands SET  deleted = 1 where id = $id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $data = array('msg' => 'Producto eliminado', 'status' => 202);
    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response->withStatus(202)->withHeader('Content-Type', 'application/json');
})->setName("deleteProducts_brand");


$app->post('/products_brands/{id}/image/', function ($request, $response, $args) {
    $dbClass = new DB(); //DB connection
    $db = $dbClass->connect();
    $logo = $request->getUploadedFiles();
    $id = $args['id'];
    
    if (empty($logo['logo'])) {
        throw new Exception('Expected a logo');
    }
    $newfile = $logo['logo'];
    if ($newfile->getError() === UPLOAD_ERR_OK) {
        
        $image_name = $newfile->getClientFilename();
        $image_path=str_replace(' ', '-', "./files/images/".$image_name);
        $newfile->moveTo($image_path);
        $query = "UPDATE products_brands SET logo = :image_path WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array('image_path'=>$image_path,'id'=>$id));
        $data = array('messsge' => 'Product_Brand Added / Modified', 'status' => 200);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response->withStatus(201);
    }
});

function moveUploadedFile($directory, $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}
?>