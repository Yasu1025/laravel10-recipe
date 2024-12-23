<?php // routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;

// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

// Home > レシピ一覧
Breadcrumbs::for('index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('レシピ一覧', route('recipe.index'));
});

// Home > レシピ一覧 > recipe
Breadcrumbs::for('show', function (BreadcrumbTrail $trail, $recipe) {
    $trail->parent('home');
    $trail->push('レシピ一覧', route('recipe.index'));
    $trail->push($recipe['title'], route('recipe.show', $recipe['id']));
});

// Home > create
Breadcrumbs::for('create', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('レシピ投稿', route('recipe.create'));
});

// Home > edit
Breadcrumbs::for('edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('home');
    $trail->push('レシピ編集', route('recipe.edit', $id));
});
