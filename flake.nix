{
  description = "PHP/Go High-Load Image Service Development Environment with NixVim";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
    nixvim = {
      url = "github:nix-community/nixvim";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs = {
    self,
    nixpkgs,
    flake-utils,
    nixvim,
  }:
    flake-utils.lib.eachDefaultSystem (
      system: let
        # Передаем allowUnfree = true в конфигурацию nixpkgs
        pkgs = import nixpkgs {
          inherit system;
          config = {
            allowUnfree = true;
          };
        };

        # PHP 8.3 со всеми необходимыми модулями
        phpDev = pkgs.php83.withExtensions ({
          enabled,
          all,
        }:
          enabled
          ++ [
            all.pdo_pgsql
            all.pgsql
            all.redis
            all.gd
            all.imagick
            all.fileinfo
            all.mbstring
            all.bcmath
            all.pcntl
          ]);

        # Кастомный Neovim через NixVim
        customNixvim = nixvim.legacyPackages.${system}.makeNixvimWithModule {
          # Явно передаем pkgs с разрешенными unfree пакетами
          inherit pkgs;
          module = {
            opts = {
              number = true;
              relativenumber = true;
              shiftwidth = 4;
              tabstop = 4;
              expandtab = true;
              smartindent = true;
              termguicolors = true;
              updatetime = 250;
              timeoutlen = 300;
            };

            colorschemes.gruvbox.enable = true;

            plugins = {
              treesitter = {
                enable = true;
                nixvimInjections = true;
              };

              cmp = {
                enable = true;
                autoEnableSources = true;
                settings = {
                  sources = [
                    {name = "nvim_lsp";}
                    {name = "path";}
                    {name = "buffer";}
                  ];
                  mapping = {
                    "<CR>" = "cmp.mapping.confirm({ select = true })";
                    "<Tab>" = "cmp.mapping.select_next_item()";
                    "<S-Tab>" = "cmp.mapping.select_prev_item()";
                  };
                };
              };

              lsp = {
                enable = true;
                servers = {
                  intelephense.enable = true;
                  ts_ls.enable = true;
                  yamlls.enable = true;
                  dockerls.enable = true;
                  sqls.enable = true;
                  html.enable = true;
                  cssls.enable = true;
                  jsonls.enable = true;
                };

                keymaps.lspBuf = {
                  "gd" = "definition";
                  "gD" = "declaration";
                  "gi" = "implementation";
                  "K" = "hover";
                  "<leader>rn" = "rename";
                  "<leader>ca" = "code_action";
                };
              };

              telescope.enable = true;
              web-devicons.enable = true;
              lualine.enable = true;
              gitsigns.enable = true;
            };
          };
        };
      in {
        devShells.default = pkgs.mkShell {
          buildInputs = with pkgs; [
            # IDE
            customNixvim

            # PHP & Composer
            phpDev
            phpDev.packages.composer

            # JS/TS Runtime
            nodejs_24
            pnpm

            # Go Toolchain
            go_1_27
            gopls
            gofumpt
            gotools
            delve

            # LSP servers
            intelephense
            typescript-language-server
            yaml-language-server
            dockerfile-language-server
            sqls

            # Image optimization
            jpegoptim
            optipng
            pngquant
            libwebp
            imagemagick

            # Benchmarks
            k6
            apacheHttpd # ab

            # Infrastructure
            docker
            docker-compose
            postgresql_16
            redis

            # Utils
            git
            jq
          ];

          shellHook = ''
            echo "🚀 PHP/Go Image Service Dev Environment Ready"
            echo "--------------------------------------------------"
            echo "PHP:      $(php -v | head -n 1)"
            echo "Neovim:   nixvim готов к работе (команда: nvim)"
            echo "--------------------------------------------------"

            if [ ! -f .env ] && [ -f .env.example ]; then
              cp .env.example .env
            fi
          '';
        };
      }
    );
}
