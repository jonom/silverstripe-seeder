<?php

use Faker\Factory;
use LittleGiant\SilverStripeSeeder\Providers\Provider;

class MemberProvider extends Provider
{
    private $faker;

    public static $shorthand = 'Member';

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public static function parseOptions($argumentString)
    {
        $args = array_map(function ($arg) {
            return trim($arg);
        }, explode(',', $argumentString));

        if (count($args) < 2) {
            throw new Exception('member provider requires an email and password to be passed as arguments');
        }

        $options = array(
            'email' => $args[0],
            'password' => $args[1],
        );

        if (count($args) >= 3) {
            $options['group'] = $args[2];
        }

        return $options;
    }

    protected function generateField($field, $state)
    {
        throw new Exception('member provider does not support generating db fields');
    }

    protected function generateHasOneField($field, $upState)
    {
        if (empty($field->arguments['email'])) {
            throw new Exception('member provider requires an email');
        }

        if (empty($field->arguments['password'])) {
            throw new Exception('member provider requires a password');
        }

        $memberClassName = $field->dataType;
        $member = new $memberClassName();
        $member->FirstName = $this->faker->firstName();
        $member->Surname = $this->faker->lastName;

        $email = $field->arguments['email'];
        $parts = explode('@', $email);

        // find unique email address
        $member->Email = $email;
        $counter = 0;
        while (Member::get()->filter('Email', $member->Email)->first()) {
            $email = $parts[0] . $counter . '@' . (isset($parts[1]) ? $parts[1] : 'domain.com');
            $member->Email = substr($email, 0, 60);
            $counter++;
        }

        $member->Password = $field->arguments['password'];


        $state = $upState->down($field, $member, 0);
        $this->writer->write($member, $field, $state);

        if (isset($field->arguments['group'])) {
            $member->addToGroupByCode('customers');
        }

        return $member;
    }
}
