<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases;

use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Domain\Repositories\UpdateJobOfferRepositoryInterface;
use Modules\Tenant\Models\JobOffer;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class UpdateobOfferUseCase
{
  public function __construct(
    protected UpdateJobOfferRepositoryInterface $repository
  ) {}

  public function execute(int $id, array $form): JsonResponse
  {
    try {

      $this->validExistOffer($id);

      $this->isFinished($id);

      $this->validPerrmission();

      $companyId = $form['companyId'] ?? null;
      $dto = new CreateJobOfferDTO(
        title: $form['title'],
        description: $form['description'],
        requirements: $form['requirements'],
        benefits: $form['benefits'],
        companyId: $companyId,
        categoryId: $form['categoryId'],
        locationId: $form['locationId'],
        contractTypeId: $form['contractTypeId'],
        scheduleId: $form['scheduleId'],
        salary: $form['salary'],
        salaryCurrency: $form['salaryCurrency'],
        address: $form['address'],
        department: $form['department'],
        province: $form['province'],
        country: $form['country'],
        publicationDate: $form['publicationDate'],
        attachments: $form['attachments'] ?? null,
      );
      $this->repository->update($id, $dto);
      return Response::success([], 'Oferta laboral actualizada correctamente');
    } catch (Exception $e) {
      return Response::error('Error: ' . $e->getMessage());
    }
  }

  private function isFinished($id)
  {
    $jobOffer = JobOffer::find($id);
    if ($jobOffer->state_id == JobOfferStateEnum::FINISHED_ID) {
      return Response::error('No se puede actualizar una oferta laboral que ya ha finalizado.');
    }
  }

  private function validExistOffer($id)
  {
    $jobOffer = JobOffer::exists($id);
    if (!$jobOffer) {
      return Response::error('No existe una oferta laboral con este id');
    }
  }
  private function validPerrmission()
  {
    $user = User::authenticated();
    $isCompany = $user->hasRole(RolTenant::COMPANY);
    $companyId = $form['companyId'] ?? null;
    if ($isCompany) {
      if ($user->company->id !== $companyId && $companyId !== null) {
        return Response::error('No tienes permiso para actualizar una oferta de trabajo para esta empresa');
      }
      $companyId = $user->company->id;
    }
  }
}
