// Compile the one theme AMD module. No dependencies are needed on a Moodle server.
import {glob, mkdir, readFile, writeFile} from 'node:fs/promises';
import {dirname} from 'node:path';
import {transformAsync} from '@babel/core';
import {minify} from 'terser';

const catalog = JSON.parse(await readFile('catalog.json', 'utf8'));
for (const variant of catalog.variants) {
    for await (const relative of glob('**/*.js', {cwd: `${variant.source}/amd/src`})) {
        const input = `${variant.source}/amd/src/${relative}`;
        const output = `${variant.source}/amd/build/${relative.replace(/\.js$/, '.min.js')}`;
        const source = await readFile(input, 'utf8');
        const transformed = await transformAsync(source, {
            filename: input,
            babelrc: false,
            configFile: false,
            plugins: [['@babel/plugin-transform-modules-amd', {
                moduleId: `theme_${variant.theme}/${relative.replace(/\.js$/, '')}`,
                moduleIds: true,
            }]],
        });
        const result = await minify(transformed.code, {format: {comments: false}});
        await mkdir(dirname(output), {recursive: true});
        await writeFile(output, `// ${variant.name} — GPL-3.0-or-later\n` + result.code + '\n');
        console.log(`Built ${output}`);
    }
}
