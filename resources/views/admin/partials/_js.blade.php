<!-- jquery-->
<script src="../admin_assets/js/vendors/jquery/jquery.min.js"></script>
<!-- bootstrap js-->
<script src="../admin_assets/js/vendors/bootstrap/dist/js/bootstrap.bundle.min.js" defer=""></script>
<script src="../admin_assets/js/vendors/bootstrap/dist/js/popper.min.js" defer=""></script>
<!--fontawesome-->
<script src="../admin_assets/js/vendors/font-awesome/fontawesome-min.js"></script>
<!-- sidebar -->
<script src="../admin_assets/js/sidebar.js"></script>
<!-- scrollbar-->
<script src="../admin_assets/js/scrollbar/simplebar.js"></script>
<script src="../admin_assets/js/scrollbar/custom.js"></script>
<!-- slick-->
<script src="../admin_assets/js/slick/slick.min.js"></script>
<script src="../admin_assets/js/slick/slick.js"></script>
<!-- datatable-->
<script src="../admin_assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
<!-- page_datatable1-->
<script src="../admin_assets/js/js-datatables/datatables/datatable.custom1.js"></script>
<!-- datatable_advance-->
<script src="../admin_assets/js/datatable/datatable_advance.js"></script>
<!-- theme_customizer-->
<script src="../admin_assets/js/theme-customizer/customizer.js"></script>
<!-- custom script -->
<script src="../admin_assets/js/script.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
            Livewire.on('showEditModal', () => {
                let modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            });

            Livewire.on('hideEditModal', () => {
                let modalEl = document.getElementById('editModal');
                let modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });

</script>