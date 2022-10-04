<?
$app->get('/products/[{id}/]', function ($request,$response,$args){

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

        if(isset($params['category'])){
            $query .= " AND category = :category ";
            $bind["category"] = $params['category'];
        }       
        
        if(isset($params['brand'])){
            $query .= " AND brand = :brand ";
            $bind["brand"] = $params['brand'];
        }

        if(isset($params['sku'])){
            $query .= " AND sku = :sku ";
            $bind["sku"] = $params['sku'];
        }

        if(isset($params['price_min']) && isset($params['price_max'])){
            $query .= " AND ( price >= :price_min AND  price <= :price_max) ";
            $bind["price_min"] = $params['price_min'];
            $bind["price_max"] = $params['price_max'];
        }
    }

    $query = "SELECT * FROM products $query";
    $stmt = $db->prepare($query);
    $stmt->execute($bind);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    if(isset($args['id']))  $products = $products[0]; //Hack para devolver un object en vez de un array.

    $response->getBody()->write(json_encode($products));
    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
 
})->setName('getProducts');


$app->post('/products/[{id}/]', function ($request,$response,$args){

    $dbClass = new DB(); //DB connection
    $db = $dbClass->connect();

    $data['id'] = null;
    if(isset($args['id'])) $data['id'] = $args['id']; 

    $inputData = $request->getParsedBody();
    
    if(is_null($inputData)){
        $response->getBody()->write(json_encode(array(
            'code' => 400,
            'message' => "Bad Request"
        )));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

  
    //LIMPIAR Y VALIDAR DATOS (CLASE DEL MIERCOLES)
    $inputs = ["name" => "bruno",'age' => "22d","comida"=>"SI"];

    $rules = [
        'name' => ['trim', 'ucfirst',"required","strip_tags"],
        'age' => ["strip_tags",'int',"required"],
        'comida' => ["string","required","strtolower","ucwords","strip_tags"],
    ];
    
    $kosher = new Kosher();
    $newInputs = $kosher->execute($inputs, $rules);

    if(!empty($kosher->error)){

        $response->getBody()->write(json_encode(array(
            'code' => 400,
            'message' => "Bad Request", 
            'error' => $kosher->error
        )));

        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode($newInputs));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
})->setName("postProduct");


//TODO pasar deleted a 1
$app->delete('/product/{id}', function ($request,$response,$args)
{
    $id = $args['id'];
    $sql = "delete from products where id = $id";
    try 
    {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        $db = null;
        $data = array('msg' => 'Producto eliminado', 'status' => 202);
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
})->setName("deleteProduct");
?>