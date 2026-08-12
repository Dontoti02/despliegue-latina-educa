<?php

namespace Modules\Tenant\Packages\Link\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Link;
use Modules\Tenant\Packages\Answer\Helpers\AnswerHelper;
use Modules\Tenant\Packages\Content\Helpers\ContentHelper;
use Modules\Tenant\Packages\Link\Helpers\LinkHelper;
use Modules\Tenant\Packages\Publication\Helpers\PublicationHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingAnswerHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingContentHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingPublicationHelper;

class LinkRepository
{
    public static function listByModel($polymorphic)
    {
        $links = $polymorphic->links()
            ->select('id', 'url')
            ->get();

        return $links;
    }

    public static function create(Request $request)
    {
        LinkHelper::validateRequest($request);

        switch ($request->model) {
            case 'content':
                $aux = ContentHelper::validateUploadFile($request);
                break;
            case 'answer':
                $aux = AnswerHelper::validateUploadFile($request);
                break;
            case 'publication':
                $aux = PublicationHelper::validateUploadFile($request);
                break;
            case 'training_content':
                $aux = TrainingContentHelper::validateUploadFile($request);
                break;
            case 'training_answer':
                $aux = TrainingAnswerHelper::validateUploadFile($request);
                break;
            case 'training_publication':
                $aux = TrainingPublicationHelper::validateUploadFile($request);
                break;
            default:
                throw new Exception('Modelo no admitido');
        }

        $aux->polymorphic->links()->create(['url' => $request->link]);

        $result = self::listByModel($aux->polymorphic);

        return $result;
    }

    public static function delete(int $id, string $model)
    {
        if (!in_array($model, ['content', 'answer', 'publication', 'training_content', 'training_answer', 'training_publication'])) {
            throw new Exception('Modelo no admitido');
        }

        $link = Link::findOrFail($id);

        switch ($model) {
            case 'content':
                ContentHelper::validateDelete('link', $link);
                break;
            case 'answer':
                AnswerHelper::validateDelete('link', $link);
                break;
            case 'publication':
                PublicationHelper::validateDelete('link', $link);
                break;
            case 'training_content':
                TrainingContentHelper::validateDelete('link', $link);
                break;
            case 'training_answer':
                TrainingAnswerHelper::validateDelete('link', $link);
                break;
            case 'training_publication':
                TrainingPublicationHelper::validateDelete('link', $link);
                break;
            default:
                throw new Exception('Modelo no admitido');
        }

        $link->delete();

        return 'Enlace eliminado correctamente';
    }
}
