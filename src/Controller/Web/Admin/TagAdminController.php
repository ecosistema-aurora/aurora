<?php

declare(strict_types=1);

namespace App\Controller\Web\Admin;

use App\Enum\FlashMessageTypeEnum;
use App\Exception\ValidatorException;
use App\Service\TagService;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class TagAdminController extends AbstractAdminController
{
    private const string ADD = 'tag/create.html.twig';
    private const string LIST = 'tag/list.html.twig';
    private const string EDIT = 'tag/edit.html.twig';

    public function __construct(
        private readonly TagService $tagService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function list(): Response
    {
        return $this->render(self::LIST, [
            'tags' => $this->tagService->list(),
        ]);
    }

    public function create(Request $request): Response
    {
        if (false === $request->isMethod(Request::METHOD_POST)) {
            return $this->render(self::ADD);
        }

        $errors = [];

        try {
            $this->tagService->create([
                'id' => Uuid::v4(),
                'name' => $request->get('name'),
            ]);

            $this->addFlash(FlashMessageTypeEnum::SUCCESS->value, $this->translator->trans('view.tag.message.created'));
        } catch (ValidatorException $exception) {
            $errors = $exception->getConstraintViolationList();
        } catch (Exception $exception) {
            $errors = [$exception->getMessage()];
        }

        if (false === empty($errors)) {
            return $this->render(self::ADD, ['errors' => $errors]);
        }

        return $this->redirectToRoute('admin_tag_list');
    }

    public function remove(string $id): Response
    {
        try {
            $this->tagService->remove(Uuid::fromString($id));
            $this->addFlashSuccess($this->translator->trans('view.tag.message.deleted'));
        } catch (Exception $exception) {
            $this->addFlashError($exception->getMessage());
        }

        return $this->redirectToRoute('admin_tag_list');
    }

    public function edit(Request $request, string $id): Response
    {
        try {
            $tag = $this->tagService->get(Uuid::fromString($id));
        } catch (Exception $exception) {
            $this->addFlashError($exception->getMessage());

            return $this->redirectToRoute('admin_tag_list');
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->render(self::EDIT, [
                'tag' => $tag,
            ]);
        }

        $errors = [];

        try {
            $this->tagService->update(Uuid::fromString($id), [
                'name' => $request->request->get('name'),
            ]);

            $this->addFlashSuccess($this->translator->trans('view.tag.message.updated'));

            return $this->redirectToRoute('admin_tag_list');
        } catch (ValidatorException $exception) {
            $errors = $exception->getConstraintViolationList();
        } catch (Exception $exception) {
            $errors = [$exception->getMessage()];
        }

        return $this->render(self::EDIT, [
            'tag' => $tag,
            'errors' => $errors,
        ]);
    }
}
