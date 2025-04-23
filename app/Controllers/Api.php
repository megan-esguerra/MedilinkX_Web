<?php

namespace App\Controllers;
use App\Models\userModel;
use CodeIgniter\RESTful\ResourceController;

class Api extends ResourceController
{
    protected $modelName = 'App\Models\userModel';
    protected $format    = 'json';

    public function index() // GET all
    {
        return $this->respond($this->model->findAll());
    }

    public function show($id = null) // GET by ID
    {
        $data = $this->model->find($id);
        return $data ? $this->respond($data) : $this->failNotFound('User not found');
    }

    // Test endpoint to verify API is working
    public function test()
    {
        return $this->respond(['message' => 'API is working!']);
    }

    public function create() // POST
    {
        try {
            // Support both JSON and form data
            $input = $this->request->getJSON(true);
            if (empty($input)) {
                $input = $this->request->getRawInput();
            }
            
            // Validate required fields
            if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
                return $this->fail('Name, email and password are required fields', 400);
            }
            
            $data = [
                'name'     => $input['name'],
                'email'    => $input['email'],
                'password' => password_hash($input['password'], PASSWORD_BCRYPT),
                'status'   => 0
            ];
            
            $result = $this->model->insert($data);
            
            if ($result === false) {
                return $this->fail($this->model->errors(), 400);
            }
            
            return $this->respondCreated(['message' => 'User created', 'id' => $result]);
        } catch (\Exception $e) {
            return $this->fail('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function update($id = null) // PUT
    {
        try {
            if (empty($id)) {
                return $this->fail('No ID provided', 400);
            }
            
            // Check if user exists
            $user = $this->model->find($id);
            if (!$user) {
                return $this->failNotFound('User not found');
            }
            
            // Support both JSON and form data
            $input = $this->request->getJSON(true);
            if (empty($input)) {
                $input = $this->request->getRawInput();
            }
            
            $data = [
                'name'   => $input['name'] ?? $user['name'],
                'email'  => $input['email'] ?? $user['email'],
                'status' => $input['status'] ?? $user['status']
            ];
            
            $result = $this->model->update($id, $data);
            
            if ($result === false) {
                return $this->fail($this->model->errors(), 400);
            }
            
            return $this->respond(['message' => 'User updated']);
        } catch (\Exception $e) {
            return $this->fail('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id = null) // DELETE
    {
        try {
            if (empty($id)) {
                return $this->fail('No ID provided', 400);
            }
            
            // Check if user exists
            if (!$this->model->find($id)) {
                return $this->failNotFound('User not found');
            }
            
            $result = $this->model->delete($id);
            
            if ($result === false) {
                return $this->fail('Failed to delete user', 500);
            }
            
            return $this->respondDeleted(['message' => 'User deleted']);
        } catch (\Exception $e) {
            return $this->fail('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}