<?php

namespace App\Admin\Controllers\Concerns;

use Dcat\Admin\Layout\Content;

trait ReadOnlyResource
{
    public function create(Content $content)
    {
        abort(403, 'Read-only admin resource.');
    }

    public function edit($id, Content $content)
    {
        abort(403, 'Read-only admin resource.');
    }

    public function store()
    {
        abort(403, 'Read-only admin resource.');
    }

    public function update($id)
    {
        abort(403, 'Read-only admin resource.');
    }

    public function destroy($id)
    {
        abort(403, 'Read-only admin resource.');
    }
}
