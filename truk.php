<?php

function genDashboard(){
    //protection admin
    if (!isConnected()){
        addFlashMessage('conectez vous'); 
        header('location: index.php?action=login'); 
        exit; 
    }
    if(!isAdmin()){
        echo 'acces forbiden réservé à l\'élite des admin'; 
        exit;
    }
    // Sélection des articles
    $articleModel = new ArticleModel();
    $articles = $articleModel->getAllArticles();

    // On récupère le message flash le cas échéant
    $flashMessage = getFlashMessage();

    // Affichage : inclusion du fichier de template
    $template = 'dashboard';
    include TEMPLATE_DIR . '/admin/baseAdmin.phtml'; 

}

function genAddArticle(){
     //protection admin
    if (!isConnected()){
        addFlashMessage('conectez vous'); 
        header('location: index.php?action=login'); 
        exit; 
    }
    if(!isAdmin()){
        echo 'acces forbiden réservé à l\'élite des admin'; 
        exit;
    }
    $titre ='';
    $contenu='';
    $categorie=''; 
    $image=''; 
    
    // Si le formulaire est soumis... 
    if (!empty($_POST)) {

        // On récupère les données du formulaire
        $titre= trim($_POST['titre']);
        $contenu = trim($_POST['contenu']);
        $categorie = (int)($_POST['categorie']);
        $image = $_FILES['image'];

       
        // Validation du formulaire (à faire en dernier quand ça fonctionne sans erreur)
        $errors = validateArticle($titre, $contenu, $categorie, $image);

        // S'il n'y a pas d'erreur, si tout est OK
        if (empty($errors)) {
            //traitement de l'image normalisation du nom dy ficheri
            $fileinfo = pathinfo($image['name']); 
            $extension = $fileinfo['extension']; 
            $filename = slugify($fileinfo['filename']).sha1(uniqid(rand(), true)).'.'.$extension;

            //deplcamet du fichier temportaure vers le dossier final*
            if(!file_exists(UPLOAD_DIR)){
                mkdir(UPLOAD_DIR); 
            }
            
            $uploadedFileSuccess = move_uploaded_file($image['tmp_name'], UPLOAD_DIR . '/' . $filename);
            if(!$uploadedFileSuccess){
                $errors['image']='errur lors du déplacement'; 
            }else {
            // On fait appel au modèle ( la fonction insertUser() ) pour insérer les données dans la table user
            $ArticleModel = new ArticleModel();
            $ArticleModel->insertArticle($titre, $contenu, $categorie, $filename);
            
            // Ajout d'un message flash en session
            addFlashMessage('Votre article a bien été créé');

            // On redirige l'internaute pour l'instant vers la page d'accueil
            header('Location: index.php');
            exit;
            }
        }
    }
    $CategoryModel = new CategoryModel();
    $categories = $CategoryModel->getAllCategories(); 
    $template = 'addArticle';
    include TEMPLATE_DIR . '/admin/baseAdmin.phtml'; 
}

function genEditArticle(){
     //protection admin
    if (!isConnected()){
        addFlashMessage('conectez vous'); 
        header('location: index.php?action=login'); 
        exit; 
    }
    if(!isAdmin()){
        echo 'acces forbiden réservé à l\'élite des admin'; 
        exit;
    }

    // Valider le paramètre idArticle
    if (!array_key_exists('idArticle', $_GET) || !$_GET['idArticle'] || !ctype_digit($_GET['idArticle'])) {
        echo '<p>ERREUR : Id Article manquant ou incorrect</p>';
        exit;
    }

    // Récupérer le paramètre idArticle
    $idArticle = (int) $_GET['idArticle']; 

    // Sélection de l'article
    $articleModel = new ArticleModel();
    $article = $articleModel->getOneArticle($idArticle);

    // Test pour savoir si l'article existe
    if (!$article) {
        echo 'ERREUR : aucun article ne possède l\'ID ' . $idArticle;
        exit;
    }
    
    $titre ='';
    $contenu='';
    $categorie=''; 
    $image=''; 
    
    // Si le formulaire est soumis... 
    if (!empty($_POST)) {

        // On récupère les données du formulaire
        $titre= trim($_POST['titre']);
        $contenu = trim($_POST['contenu']);
        $categorie = (int)($_POST['categorie']);
        $image = $_FILES['image'];
       
        // Validation du formulaire (à faire en dernier quand ça fonctionne sans erreur)
        $errors = validateArticle($titre, $contenu, $categorie, $image);
        // S'il n'y a pas d'erreur, si tout est OK
        if (empty($errors)) {

            // On fait appel au modèle ( la fonction insertUser() ) pour insérer les données dans la table user
            $ArticleModel = new ArticleModel();
            $ArticleModel->editArticle($titre, $contenu, $categorie, $image, $idArticle);
            
            // Ajout d'un message flash en session
            addFlashMessage('Votre article a bien été modifié');

            // On redirige l'internaute pour l'instant vers la page d'accueil
            header('Location: index.php?action=admin');
            exit;
            
            
        }
    }
    $CategoryModel = new CategoryModel();
    $categories = $CategoryModel->getAllCategories(); 
    $template = 'editArticle';
    include TEMPLATE_DIR . '/admin/baseAdmin.phtml'; 

}

function validateArticle(string $titre, string $contenu, string $categorie, array $image): array
{
     //protection admin
    if (!isConnected()){
        addFlashMessage('conectez vous'); 
        header('location: index.php?action=login'); 
        exit; 
    }
    if(!isAdmin()){
        echo 'acces forbiden réservé à l\'élite des admin'; 
        exit;
    }
    $errors = [];

    // LASTNAME
    if (!$titre) { 
        $errors['titre'] = 'Le champ "Titre" est obligatoire';
    }

    // FIRSTNAME
    if (!$contenu) { 
        $errors['contenu'] = 'Le champ "Contenu" est obligatoire';
    }

    // VALIDATION EMAIL
    if (!$categorie) { // ou bien if (empty($email)) { ou if (strlen($email) == 0) { ou if ($email == '') { 
        $errors['categorie'] = 'Le champ "Catégories" est obligatoire';
    }

    // FIRSTNAME
    if (!$image) { 
        $errors['image'] = 'Le champ "image" est obligatoire';
    }

     //est ce que le fichier a été transmis
     if ($image['error']==UPLOAD_ERR_NO_FILE){
        $errors['image']='mettre une image obligatoire'; 
    } elseif($image['error'] != UPLOAD_ERR_OK){
        $errors['image'] = 'erreur lors du téchérrgementkh';
    }else{
        $allowedMimeTypes= ['image/gif','image/jpeg','image/png'];
        $mimetype = mime_content_type($image['tmp_name']);
        if (!in_array($mimetype, $allowedMimeTypes)){
            $errors['image']='jpeg, png ou gif, no rien d\'autre';
        }else{
            $maxUploadSize = 1048576; //1mo
            if(filesize($image['tmp_name'])> $maxUploadSize){
                $errors['image'] = 'trop colossal + 1mo wesh !! '; 
            }
        }
    }

    // Retourne le tableau d'erreurs
    return $errors;
}

function genSupprArticle(){
    //protection admin
    if (!isConnected()){
        addFlashMessage('conectez vous'); 
        header('location: index.php?action=login'); 
        exit; 
    }
    if(!isAdmin()){
        echo 'acces forbiden réservé à l\'élite des admin'; 
        exit;
    }
    $titre ='';
    $contenu='';
    $categorie=''; 
    $image=''; 
    

    // Valider le paramètre idArticle
    if (!array_key_exists('idArticle', $_GET) || !$_GET['idArticle'] || !ctype_digit($_GET['idArticle'])) {
        echo '<p>ERREUR : Id Article manquant ou incorrect</p>';
        exit;
    }

    // Récupérer le paramètre idArticle
    $idArticle = (int) $_GET['idArticle']; 

    // Sélection de l'article
    $articleModel = new ArticleModel();
    $article = $articleModel->getOneArticle($idArticle);

    // Test pour savoir si l'article existe
    if (!$article) {
        echo 'ERREUR : aucun article ne possède l\'ID ' . $idArticle;
        exit;
    }

    // On fait appel au modèle ( la fonction insertUser() ) pour insérer les données dans la table user
    $ArticleModel = new ArticleModel();
    $ArticleModel->supprArticle($idArticle);
    
    // Ajout d'un message flash en session
    addFlashMessage('Votre article a bien été supprimé');

    // On redirige l'internaute pour l'instant vers la page d'accueil
    header('Location: index.php?action=admin');
    exit;
    
    
}
