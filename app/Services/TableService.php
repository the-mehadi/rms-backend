<?php

namespace App\Services;

use App\Models\Table;

class TableService
{
    /**
     * Get all tables ordered by table_number ASC.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        // return Table::with('activeOrder')->orderBy('table_number')->get();
        return Table::with('activeOrder')
            ->orderBy('table_number')
            ->get();
    }

    /**
     * Get a single table by ID.
     *
     * @param int $id
     * @return Table
     */
    public function getById(int $id): Table
    {
        return Table::findOrFail($id);
    }

    /**
     * Create a new table.
     *
     * @param array $data
     * @return Table
     */
    public function store(array $data): Table
    {
        return Table::create($data);
    }

    /**
     * Update a table.
     *
     * @param Table $table
     * @param array $data
     * @return Table
     */
    public function update(Table $table, array $data): Table
    {
        $table->update($data);
        return $table;
    }

    /**
     * Update table status.
     *
     * @param Table $table
     * @param string $status
     * @return Table
     */
    public function updateStatus(Table $table, string $status): Table
    {
        $table->update(['status' => $status]);
        return $table;
    }
}
