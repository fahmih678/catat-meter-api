<style>
        .stat-card-primary,
        .stat-card-success,
        .stat-card-warning,
        .stat-card-info {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card-primary:hover,
        .stat-card-success:hover,
        .stat-card-warning:hover,
        .stat-card-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }

        /* Custom input group styling */
        .input-group .btn {
            border-left: none;
        }

        .input-group .btn:hover {
            border-left: none;
            z-index: 1;
        }

        /* Modal animations */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translate(0, -50px);
        }

        .modal.show .modal-dialog {
            transform: translate(0, 0);
        }

        /* Form validation styling */
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
            padding-right: 0.75rem;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        /* Button loading state */
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 1rem;
            }

            .input-group .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }
        }
    </style>