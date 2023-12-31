<!-- 
    This php page reads the user's order and displays a summary of the order
    along with the pickup time.
 -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Order Confirmation</title>
    <link href="styles.css" rel="stylesheet" type="text/css" />
    <style>
        body, html {
            background-color: #7b5946;
        }
        h2 {
            position: relative;
            display: block;
            margin: 0 auto;
            margin-top: 40px;
            margin-bottom: 30px;
            width: 100%;
            border-radius: 10px;
            max-width: 500px;
            text-align: center;
            font-size: 36px;
            color: blanchedalmond;
        }
        /* table styling for showing the order summary */
        table {
            position: relative;
            box-sizing: border-box;
            table-layout: fixed;
            width: 100%;
            max-width: 800px;
            border-collapse: collapse;
            border: 2px solid #7b5946;
            border-radius: 10px;
            margin: 0 auto;
            margin-top: 40px;
            margin-bottom: 50px;
            background-color: blanchedalmond;
        }
        tr {
            border-bottom: 2px solid #cecece;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
        }
        th, td {
            box-sizing: border-box;
            width: 100%;
            display: inline-block;
            text-align: right;
        }
        td:nth-child(2) {
            max-width: 100px;
        }
        td:nth-child(3) {
            max-width: 110px;
        }
        td:last-child {
            max-width: 110px;
        }
        th:first-child, td:first-child {
            text-align: left;
            max-width: 200px;
        }
    </style>
</head>

<body>
<?php
    // header and title of page
    include 'header.php';
    echo "<h2>Order Confirmation</h2>";

    // CONSTANTS
    define("TAXRATE", 0.0625);

    // OBJECTS
    // the Item object represents a food item that the customer has ordered
    // storing the item name, unit price, and quantity ordered
    class Item {
        public $name;
        public $price;
        public $qty;
    
        public function __construct($itemName, $unitPrice, $quantity) {
            $this->name = $itemName;
            $this->price = $unitPrice;
            $this->qty = $quantity;
        }
    }

    // FUNCTIONS
    /**
     * formats the given amount as a currency string.
     * 
     * @param {number} amount - a numeric amount to format as currency.
     * @throws - will throw error if amount is not a number.
     * @return {string} - $ followed by amount padded with up to 2 trailing zeros.
     */
    function currencyStr($amount) {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException("Amount cannot be formatted as currency");
        }

        // round to two decimal places and split around the '.'
        $rounded = round($amount, 2);
        $exploded = explode(".", $rounded);

        // no '.' exists
        if (count($exploded) == 1) {
            $rounded .= ".00";
        } // '.' exists but there is only one 0
        else if (strlen($exploded[1]) === 1) {
            $rounded .= "0";
        }

        return "$" . $rounded;
    }
    
    // creates a table row with four data cells
    /**
     * creates a new receipt row with four columns.
     * 
     * @param {string} name - name of item or field.
     * @param {number} price - unit price of item.
     * @param {*} qty - quantity of item.
     * @param {number} totalCost - total cost, accounting for quantity; or subtotal.
     * @return {string} - a tr element containing all the given details.
     */
    function newReceiptRow($name, $price, $qty, $totalCost) {
        if ($price !== "&nbsp;") {
            $price = currencyStr($price);
        }
        $cost = currencyStr($totalCost);
        $rowData = "<tr><td>$name</td><td>$qty</td><td>$price</td><td>$cost</td></tr>";
        return $rowData;
    }
    
    // read form data and save as an array of Item objects
    $formData = array();
    foreach ($_GET as $key => $val) {
        // keys begin as 'qty', 'name', or 'price'.
        // if the key is qty*, check if the quantity (val) is nonzero. 
        // That means the user ordered this item. So save this item with its
        // name, unit price, and quantity ordered
        $keyType = substr($key, 0, 3);

        if ($keyType == 'qty' && $val > 0) {
            $itemId = substr($key, 3);
            $name = $_GET["name$itemId"];
            $price = $_GET["price$itemId"];
            $formData[] = new Item($name, $price, $val);
        }
    }
    
    $subtotal = 0;
    $receiptRows = "";
    
    // create a row for each item ordered, and calculate subtotal
    foreach ($formData as $item) {
        $cost = $item->price * $item->qty;
        $receiptRows .= newReceiptRow($item->name, $item->price, $item->qty, $cost);
        $subtotal += $cost;
    }

    // calculate tax and final total
    $tax = $subtotal * TAXRATE;
    $total = $subtotal + $tax;

    // create rows for subtotal, tax, and total
    $showSubtotal = newReceiptRow("SUBTOTAL", "&nbsp;", "&nbsp;", $subtotal);
    $showTax = newReceiptRow("TAX", "&nbsp;", "&nbsp;", $tax);
    $showTotal = newReceiptRow("TOTAL", "&nbsp;", "&nbsp;", $total);

    // write the whole receipt table to the document
    echo <<<HTML
    <table id="receipt">
        <tr>
            <th>ITEM</th>
            <th>QUANTITY</th>
            <th>UNIT PRICE</th>
            <th>TOTAL COST</th>
        </tr>
        $receiptRows
        $showSubtotal
        $showTax
        $showTotal
    </table>
    HTML;
?>

<script>
    // append to the table the pickup time based on the user's timezone
    const userTime = new Date();
    
    // add 20 minutes to user's current time
    userTime.setMinutes(userTime.getMinutes() + 20);

    const day = userTime.toLocaleDateString();
    const ts = userTime.toLocaleTimeString('en-US', { hour12: true, hour: "numeric", minute: "numeric"});
    const timeHtml = `<tr><td>PICKUP TIME</td><td>&nbsp;</td><td>&nbsp;</td><td>${day}<br />${ts}</td></tr>`;

    document.getElementById("receipt").innerHTML += timeHtml;
</script>
</body>
</html>
