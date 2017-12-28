

@servers(['web' => 'ubuntu@www.sir-edgar.com'])

@task('deploy', ['on' => ['web']])

    cd ProductivityApp/

    php artisan down

    @if ($branch)
        git checkout  {{ $branch }}

        git pull origin {{ $branch }}
    @else
        git pull
    @endif

    composer install --optimize-autoloader

    php artisan migrate --force

    php artisan config:cache

    pho artisan route:cache

    php artisan horizon:terminate

    php artisan up


@endtask