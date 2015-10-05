<?php namespace Wn\Generators\Commands;


class MigrationCommand extends BaseCommand {

	protected $signature = 'wn:migration
        {className : The table name.}
        {--schema= : the schema.}
        {--keys= : foreign keys.}
        {--table= : name of the migration file.}
        {--parsed : tells the command that arguments have been already parsed. To use when calling the command from an other command and passing the parsed arguments and options}
        ';
        // {action : One of create, add, remove or drop options.}
        // The action is only create for the moment

	protected $description = 'Generates a migration to create a table with schema';

    public function handle()
    {
        $className = $this->argument('className');
        $name = ucwords(camel_case($className));

        $table = $this->option('table')?$this->option('table'):'TableName';

        $file = date('Y_m_d_His_') . snake_case($name);

        $content = $this->getTemplate('migration')
            ->with([
                'table' => $table,
                'name' => $name,
                'schema' => $this->getSchema(),
                'constraints' => $this->getConstraints()
            ])
            ->get();

        $this->save($content, "./database/migrations/{$file}.php");

        $this->info("{$name} migration generated !");
    }

    protected function getSchema()
    {
        $schema = $this->option('schema');
        if(! $schema){
            return "            // Schema declaration";
        }

        $items = $schema;
        if( ! $this->option('parsed')){
            $items = $this->getArgumentParser('schema')->parse($schema);
        }

        $fields = [];
        foreach ($items as $item) {
            $fields[] = $this->getFieldDeclaration($item);
        }

        return implode(PHP_EOL, $fields);
    }

    protected function getFieldDeclaration($parts)
    {
        $name = $parts[0]['name'];
        $parts[1]['args'] = array_merge(["'{$name}'"], $parts[1]['args']);
        unset($parts[0]);
        $parts = array_map(function($part){
            return '->' . $part['name'] . '(' . implode(', ', $part['args']) . ')';
        }, $parts);
        return "            \$table" . implode('', $parts) . ';';
    }

    protected function getConstraints()
    {
        $keys = $this->option('keys');
        if(! $keys){
            return "            // Constraints declaration";
        }

        $items = $keys;
        if(! $this->option('parsed')){
            $items = $this->getArgumentParser('foreign-keys')->parse($keys);
        }

        $constraints = [];
        foreach ($items as $item) {
            $constraints[] = $this->getConstraintDeclaration($item);
        }

        return implode(PHP_EOL, $constraints);
    }

    protected function getConstraintDeclaration($key)
    {
        if(! $key['column']){
            $key['column'] = 'id';
        }
        if(! $key['table']){
            $key['table'] = str_plural(substr($key['name'], 0, count($key['name']) - 4));
        }

        $constraint = $this->getTemplate('migration/foreign-key')
            ->with([
                'name' => $key['name'],
                'table' => $key['table'],
                'column' => $key['column']
            ])
            ->get();

        if($key['on_delete']){
            $constraint .= PHP_EOL . $this->getTemplate('migration/on-constraint')
                ->with([
                    'event' => 'Delete',
                    'action' => $key['on_delete']
                ])
                ->get();
        }

        if($key['on_update']){
            $constraint .= PHP_EOL . $this->getTemplate('migration/on-constraint')
                ->with([
                    'event' => 'Update',
                    'action' => $key['on_update']
                ])
                ->get();
        }

        return $constraint . ';';
    }
    
}