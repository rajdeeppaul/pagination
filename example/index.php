<!DOCTYPE html>
<html>
    <head>
        <title>World Data</title>
    </head>
    <body>
        <?php
            require_once('pagination.php');

            $pg = new Pagination('pdo', 'localhost', 'root', 'root', 'world');
            $pg->setPaginationParameters();
            
            $resultSet = $pg->getResult('SELECT * FROM city WHERE CountryCode = ?', array('IND'), $_GET, 'page', true);
            echo "<table border='2'>";
                echo "<tr>";
                echo "<td>ID</td>";
                echo "<td>Name</td>";
                echo "<td>CountryCode</td>";
                echo "<td>District</td>";
                echo "<td>Population</td>";
                echo "</tr>";
                
                foreach($resultSet as $row){
                    echo "<tr>";
                        echo "<td>".$row['ID']."</td>";
                        echo "<td>".$row['Name']."</td>";
                        echo "<td>".$row['CountryCode']."</td>";
                        echo "<td>".$row['District']."</td>";
                        echo "<td>".$row['Population']."</td>";
                    echo "</tr>";
                }
                
                echo "<tr style='text-align:center;'><td colspan='5'>"; 
                $pgLinks = $pg->getPaginationLinks();
                if(is_array($pgLinks) && count($pgLinks) && $pgLinks['prev']){
                    echo '&laquo;&nbsp;';
                }
                if(is_array($pgLinks) && count($pgLinks) && count($pgLinks['links'])){
                    foreach($pgLinks['links'] as $link){
                        echo '<a href="index.php?page='.$link.'">'.$link.'</a>&nbsp;';
                    }
                }
                if(is_array($pgLinks) && count($pgLinks) && $pgLinks['next']){
                    echo '&raquo;';
                }
                echo "</td></tr>";
            echo"</table>";
        ?>
    </body>
</html>

