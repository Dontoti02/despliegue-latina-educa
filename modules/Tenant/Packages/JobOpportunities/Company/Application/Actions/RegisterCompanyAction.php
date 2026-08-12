<?php

namespace Modules\Tenant\Packages\JobOpportunities\Company\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Packages\JobOpportunities\Company\Application\DTOs\RegisterCompanyDTO;
use Modules\Tenant\Models\JobCompany;
use Modules\Tenant\Models\SystemConfiguration;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\Rol;
use Modules\Tenant\Models\User;

class RegisterCompanyAction
{
  public function execute(RegisterCompanyDTO $dto): JobCompany
  {
    DB::beginTransaction();
    try {
      $company = $this->createCompany($dto);
      DB::commit();
      return $company;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function createCompany(RegisterCompanyDTO $dto): JobCompany
  {
    $company = JobCompany::create([
      'name'        => $dto->name,
      'ruc'         => $dto->ruc,
      'email'       => $dto->email,
      'phone'       => $dto->phone,
      'mailbox'     => $dto->mailbox,
      'description' => $dto->description,
      'website'     => $dto->website,
      'address'     => $dto->address,
    ]);

    $person = Person::create([
      'document_number' => $dto->ruc,
      'names'           => $dto->name,
      'email'           => $dto->email,
      'phone'           => $dto->phone,
      'sex'             => null,
      'birth_date'      => null,
      'native_language' => null,
    ]);

    $password = $dto->ruc;

    $user = User::create([
      'person_id' => $person->id,
      'company_id' => $company->id,
      'email'     => $dto->email,
      'password'  => Hash::make($password)
    ]);

    $rolId = Rol::where('id', RolTenant::COMPANY)->first()?->id;

    if ($rolId) {
      $user->roles()->attach($rolId);
    }

    if ($dto->email) {
      $institutionName = SystemConfiguration::byKey('key', 'application_name');
      $this->notifyCompanyAccessCredentials([
        'subject' => '¡Gracias por registrarte!',
        'to' => $dto->email,
        'cc' => [],
        'institutionName' => $institutionName->value,
        'companyName' => $company->name,
        'email' => $dto->email,
        'loginUrl' => request()->header('x-subdomain') . '/login',
        'password' => $password,
      ]);
    }
    return $company;
  }

  private function notifyCompanyAccessCredentials($data): void
  {
    $subject = $data['subject'];
    $to = $data['to'];
    $cc = $data['cc'] ?? [];
    $institutionName = $data['institutionName'];
    $companyName = $data['companyName'];
    $email = $data['email'];
    $loginUrl = $data['loginUrl'];
    $password = $data['password'];
    $body = EmailBodyTemplate::companyCredentials;
    $body = str_replace('{{institutionName}}', $institutionName, $body);
    $body = str_replace('{{companyName}}', $companyName, $body);
    $body = str_replace('{{email}}', $email, $body);
    $body = str_replace('{{loginUrl}}', $loginUrl, $body);
    $body = str_replace('{{password}}', $password, $body);

    $data = (object) [
      'subject' => $subject,
      'body' => $body,
      'to' => $to,
      'cc' => $cc,
    ];

    MailerService::send($data);
  }
}
