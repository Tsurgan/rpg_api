<?php


class RPG_class
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    function create_user() {
        $user_name = trim($_POST["user_name"]);

        $stmt = $this->conn->prepare("INSERT INTO user (name) VALUES (?)");
		$stmt->execute([$user_name]);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo "Был успешно создан пользователь $user_name";
    }
    function create_quest() {
        $quest_name = trim($_POST["quest_name"]);
        $quest_worth = $_POST["quest_worth"];

        $stmt = $this->conn->prepare("INSERT INTO quest (name,cost) VALUES (?,?)");
		$stmt->execute([$quest_name,$quest_worth]);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo "Было успешно создано задание $quest_name";
    }
    function close_quest() {
        $user_id = $_POST["user_id"];
        $quest_id = $_POST["quest_id"];

        // get quest worth by id
        $stmt1 = $this->conn->prepare("SELECT cost FROM quest WHERE ID = ?");
        $stmt1->execute([$quest_id]);
		$data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);


        // set quest status as closed
        $stmt2 = $this->conn->prepare("UPDATE quest SET status = 1 WHERE ID = ?");
		$stmt2->execute([$quest_id]);
		$data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // update user.balance by adding cost to the balance
        $stmt3 = $this->conn->prepare("UPDATE user SET balance = balance + ? WHERE ID = ?");
		$stmt3->execute([$data1[0]['cost'],$user_id]);
		$data3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

		//insert into history
        $stmt = $this->conn->prepare("INSERT INTO history (user_id,quest_id) VALUES (?,?)");
		$stmt->execute([$user_id,$quest_id]);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo "Задание $quest_id было закрыто пользователем $user_id.";
    }
    function get_hist() {
        $user_get = $_GET["user_get"];
        $quest_id_list=[];
        $user_balance=0;
        //get history from relevant user from history
            //get quest_ids from history by user_id
        $stmt1 = $this->conn->prepare("SELECT quest_id FROM history WHERE user_id = ?");
        $stmt1->execute([$user_get]);
		$data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data1 as $key => $value) {
            $quest_id_list[]= $value['quest_id'];
        }
        if (count($quest_id_list)>0) {
		        //get names from quest by quest_ids
		    $in  = str_repeat('?,', count($quest_id_list) - 1) . '?';
            $stmt2 = $this->conn->prepare("SELECT q.name, q.ID, h.quest_id FROM quest q INNER JOIN history h ON q.ID = h.quest_id WHERE q.ID in ($in)");
		    $stmt2->execute($quest_id_list);
		    $data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            //get balance from users
            $stmt3 = $this->conn->prepare("SELECT balance FROM user WHERE ID = ?");
		    $stmt3->execute([$user_get]);
		    $data3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            echo "История пользователя $user_get: <br>";
		    foreach ($data2 as $key => $value) {
		        echo $value['name']."<br>";
		    }
		    $user_balance = $data3[0]['balance'];
		    echo "Баланс пользователя $user_get: $user_balance";
        }
        else {
            echo "Этот пользователь еще не завершал заданий.";
        }
    }
}


try {
    $conn = new PDO( 'mysql:host=localhost;dbname=rpg_db', 'root', '');
    $rpg = new RPG_class($conn);

    if(array_key_exists('create_user', $_POST)) {
        $rpg->create_user();
    }
    else if(array_key_exists('create_quest', $_POST)) {
        $rpg->create_quest();
    }
    else if(array_key_exists('close_quest', $_POST)) {
        $rpg->close_quest();
    }
    else if(array_key_exists('get_hist', $_GET)) {
        $rpg->get_hist();
    }
}
catch (PDOException $e){
    echo $e->getMessage();
}



?>

<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<html>

<body>

    <form action="" method="post">
        <div>
            <label for="user_name">Введите имя пользователя:</label>
            <input type="text" name="user_name" id ="user_name" required/>
        </div>
        <div>
            <input type="submit" name="create_user" id="create_user" value="Создать пользователя">
        </div>
    </form>

<br>

    <form action="" method="post">
        <div>
            <label for="quest_name">Введите название и награду:</label>
            <input type="text" name="quest_name" id ="quest_name" required/>
            <input type="number" name="quest_worth" id ="quest_worth" value = 0 />
        </div>
        <div>
            <input type="submit" name="create_quest" id="create_quest" value="Создать задание">
        </div>
    </form>

<br>

    <form action="" method="post">
        <div>
            <label for="user_id">Введите ID пользователя:</label>
            <input type="number" name="user_id" id ="user_id" required/>

            <label for="quest_id">Введите ID задания:</label>
            <input type="number" name="quest_id" id ="quest_id" required/>
        </div>
        <div>
            <input type="submit" name="close_quest" id="close_quest" value="Закрыть задание и начислить награду">
        </div>
    </form>

<br>

    <form action="" method="get">
        <div>
            <label for="user_get">Введите ID пользователя:</label>
            <input type="number" name="user_get" id ="user_get" required/>
        </div>
        <div>
            <input type="submit" name="get_hist" id="get_hist" value="Получить историю и баланс пользователя">
        </div>
    </form>
</body>
</html>
