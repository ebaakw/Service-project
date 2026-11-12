cd /home/master/applications/jnqpzfezfw/public_html
. "$HOME/.nvm/nvm.sh" && nvm use 22
bash deploy.sh
php artisan migrate 
php artisan optimize:clear
php artisan optimize
ls -la public/build/manifest.json




--force
php artisan db:seed --class=DemoCompanyTechnicianCustomerSeeder --force
php artisan db:seed --class=ContentPagesSeeder --force