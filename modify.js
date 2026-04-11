const fs = require('fs');
let content = fs.readFileSync('resources/js/Pages/Admin/Settings/Index.vue', 'utf8');

const generalCardMatch = content.match(/(<Card class="p-4 sm:p-6 max-w-2xl">\s*<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ajustes Generales<\/h3>[\s\S]*?<\/Card>)/);
const factsCardMatch = content.match(/(<Card class="p-4 sm:p-6 max-w-2xl">\s*<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Datos de Empresa \/ Facturación<\/h3>[\s\S]*?<\/Card>)/);
const pushCardMatch = content.match(/(<Card class="p-4 sm:p-6 max-w-2xl">\s*<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notificaciones Push<\/h3>[\s\S]*?<\/Card>)/);

const targetRegex = /<div class="py-6 space-y-6">[\s\S]*<\/div>\s*<\/AuthenticatedLayout>/;

const newLayout = `
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
                <div class="w-full">
                    ${factsCardMatch[1].replace('max-w-2xl', 'w-full')}
                </div>
                
                <div class="space-y-6 w-full">
                    ${generalCardMatch[1].replace('max-w-2xl', 'w-full')}
                    ${pushCardMatch[1].replace('max-w-2xl', 'w-full')}
                </div>
            </form>
        </div>
    </AuthenticatedLayout>`;

content = content.replace(targetRegex, newLayout.trim());
fs.writeFileSync('resources/js/Pages/Admin/Settings/Index.vue', content);
console.log("Success");
