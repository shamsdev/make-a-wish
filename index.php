<?php
$namesFile = './data/names.txt';

function encode($number)
{
    return strtr(rtrim(base64_encode(pack('i', $number)), '='), '+/', '-_');
}

function decode($base64)
{
    $number = unpack('i', base64_decode(str_pad(strtr($base64, '-_', '+/'), strlen($base64) % 4, '=')));
    return $number[1];
}

function getCurrentUri()
{
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = trim($uri, '/');
    return $uri;
}

function GetEmailContent($name, $message)
{
    return '<!DOCTYPE html><html><head><meta name=viewport content="width=device-width, initial-scale=1"></head><style>.container{box-sizing:border-box;margin:20px auto;padding-left:20px;padding-right:20px;padding-bottom:20px;border:2px solid rgba(162,162,162,0.55);border-radius:10px;width:100%;max-width:500px;text-align:center}.detailsBox{text-align:center;color:#505050}</style><body><div class="container"><p>Hello Dear ' . $name . ', Someone makes a wish for you :</p> <q cite="https://www.imdb.com/title/tt0062622/quotes/qt0396921"> <?php echo $message ?>' . $message . '</q> <br></div><div class="detailsBox"><p> <a href="http://shamsdev.com/make-a-wish" target="_blank">Make a Wish!</a> | <a href="http://shamsdev.com/" target="_blank">ShamsDEV.com</a></p></div></body></html>';
}

function SendMail($email, $name, $message)
{
    require_once('./phpmailer/class.phpmailer.php');
    require_once('./values/smtp_values.php');
    try {
        $mailer = new PHPMailer();
        $body = GetEmailContent($name, $message);
        $mailer->IsSMTP();
        $mailer->SMTPDebug = 1;
        $mailer->SMTPAuth = true;
        $mailer->Host = SmtpValues::$Host;
        $mailer->Port = SmtpValues::$Port;
        $mailer->Username = SmtpValues::$Username;
        $mailer->Password = SmtpValues::$Password;

        $mailer->SetFrom(SmtpValues::$Address, 'Make a Wish!');
        $mailer->Subject = "Someone Makes a Wish For You!";
        $mailer->MsgHTML($body);
        $mailer->AddAddress($email, $name);
        return $mailer->Send();
    } catch (Exception $exception) {
        return false;
    }
}

function handleSendMessage($namesFile)
{
    if (isset($_POST['id']) && isset($_POST['msg'])) {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
        $msg = filter_var($_POST['msg'], FILTER_SANITIZE_STRING);

        $lines = file($namesFile);
        $personData = explode(" ", $lines[$id]);

        if (SendMail($personData[1], $personData[0], $msg))
            echo 'suc';
        else
            echo 'err';
    } else {
        echo 'err';
    }
    exit();
}

function handleCreateLink($namesFile)
{
    if (isset($_POST['name']) && isset($_POST['mail'])) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $mail = filter_var($_POST['mail'], FILTER_SANITIZE_STRING);

        $lines = file($namesFile);
        file_put_contents($namesFile, PHP_EOL . $name . " " . $mail, FILE_APPEND | LOCK_EX);
        echo "http://shamsdev.com/make-a-wish/" . encode(sizeof($lines));
        exit();
    } else {
        echo 'err';
    }
    exit();
}

$uri = getCurrentUri();
if (substr($uri, 0, 1) === "@") {
    switch (substr($uri, 1)) {
        case "send_message":
            handleSendMessage($namesFile);
            break;

        case "create_link":
            handleCreateLink($namesFile);
            break;
    }
} else {
    $lines = file($namesFile);
    $dataLineNumber = strlen($uri) > 0 ? decode($uri) : 0;
    if ($dataLineNumber >= sizeof($lines) || $dataLineNumber < 0)
        $dataLineNumber = 0;

    $personData = explode(" ", $lines[$dataLineNumber]);
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Make A Wish To Me :)</title>
    <meta name=description
          content="Make a wish for your friend">
    <meta name=author content="Mohammad Shams">
    <meta name=viewport content="width=device-width, initial-scale=1">
    <link rel=stylesheet href=css/stylesheet.css>
    <script src=scripts/jquery.js></script>
</head>
<body>
<div class="wrapper">
    <div id="send_wish_panel" class="container center">
        <h1>Make A Wish For <? echo $personData[0]; ?></h1>
        <form id=send_message_form class="form">
            <textarea class="input" id="message-area" placeholder="Enter your wish"></textarea>
            <button id="send-btn" type="submit">Send</button>
        </form>
        <a href="#" id="get_wish_link_btn" onclick="createWishLinkBtn();">Get your own wish link</a>
        <br>
        <br>
        <a href="https://github.com/shamsdev/make-a-wish" target="_blank">
            <img border="0" src="https://image.flaticon.com/icons/svg/733/733553.svg" width="32" height="32">
        </a>
    </div>

    <div id="get_wish_link" class="container center" style="display: none;">
        <h1>Create Your Own Wish Link</h1>
        <form id="create_wish_link_form" class="form">
            <input class="input" id="name" placeholder="Enter your Name"/>
            <input class="input" id="mail" placeholder="Enter your Email"/>
            <textarea style="display: none;" class="input" readonly
                      id="link" placeholder="http://shamsdev.com"></textarea>
            <button id="create-btn" type="submit">Create</button>
        </form>
        <br>
        <br>
        <a href="https://github.com/shamsdev/make-a-wish" target="_blank">
            <img border="0" src="https://image.flaticon.com/icons/svg/733/733553.svg" width="32" height="32">
        </a>
    </div>
</div>

<script type=text/javascript>


    $("#send_message_form").submit(function (g) {
        g.preventDefault();
        const sendBtn = document.getElementById("send-btn");
        const messageArea = document.getElementById("message-area");

        if (messageArea.value.trim().length === 0)
            return;

        const form = $(this);
        messageArea.disabled = true;
        sendBtn.innerHTML = "Sending ...";
        sendBtn.disabled = true;
        sendBtn.className = "gray";

        let action = "@send_message";
        let post = $.post(action, {id: <?php echo $dataLineNumber?> , msg: $("#message-area").val()});
        post.done(function (response) {
            if (response.toString() === "suc") {
                sendBtn.className = "green";
                sendBtn.innerHTML = "Sent!";
            } else {
                sendBtn.className = "red";
                sendBtn.innerHTML = "Some Error Occurred! Try Again";
                sendBtn.disabled = false;
                messageArea.disabled = false;
            }
        })
    });

    $("#create_wish_link_form").submit(function (g) {
        g.preventDefault();
        const createBtn = document.getElementById("create-btn");
        const name = document.getElementById("name");
        const mail = document.getElementById("mail");

        if (name.value.trim().length === 0 || mail.value.trim().length === 0)
            return;

        const form = $(this);
        name.disabled = true;
        mail.disabled = true;
        createBtn.disabled = true;

        createBtn.innerHTML = "Creating ...";
        createBtn.className = "gray";

        let action = "@create_link";
        let post = $.post(action, {name: name.value, mail: mail.value});
        post.done(function (response) {
            if (response.toString() !== "err") {
                alert(response.toString());
                name.style.display = "none";
                mail.style.display = "none";
                const link = document.getElementById("link");
                link.style.display = "block";
                link.value = response.toString();

                createBtn.className = "green";
                createBtn.innerHTML = "Created! Copy your link and share with your friends!";
            } else {
                createBtn.className = "red";
                createBtn.innerHTML = "Some Error Occurred! Try Again";
                createBtn.disabled = false;
                name.disabled = false;
                mail.disabled = false;
            }
        })
    });

    function createWishLinkBtn(g) {
        document.getElementById("send_wish_panel").style.display = "none";
        document.getElementById("get_wish_link").style.display = "block";
        g.preventDefault();
    }

</script>
</body>
</html>