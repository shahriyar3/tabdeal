# Mautic Tabdeal Makefile

.PHONY: help install start stop restart logs clean migrate assets test

help:
	@echo "دستورات موجود:"
	@echo "  install   - نصب و راه‌اندازی کامل پروژه"
	@echo "  start     - راه‌اندازی سرویس‌ها"
	@echo "  stop      - توقف سرویس‌ها"
	@echo "  restart   - راه‌اندازی مجدد سرویس‌ها"
	@echo "  logs      - نمایش لاگ‌ها"
	@echo "  clean     - پاک کردن cache و logs"
	@echo "  migrate   - اجرای migration ها"
	@echo "  assets    - build کردن assets"
	@echo "  test      - اجرای تست‌ها"
	@echo "  backup    - backup از دیتابیس"
	@echo "  restore   - restore دیتابیس"


install:
	@echo "📦 نصب و راه‌اندازی پروژه..."
	@if [ ! -f .env ]; then \
		echo "📝 ایجاد فایل .env..."; \
		cp env.example .env; \
		echo "⚠️  لطفاً فایل .env را ویرایش کنید"; \
	fi
	@echo "🐳 راه‌اندازی Docker containers..."
	docker compose up -d --build
	@echo "⏳ منتظر آماده شدن سرویس‌ها..."
	@sleep 30
	@echo "🗄️  اجرای migration ها..."
	docker compose exec -T php bin/console doctrine:migrations:migrate --no-interaction
	@echo "✅ نصب کامل شد!"
	@echo "🌐 Mautic در آدرس http://localhost:8080 در دسترس است"
	@echo "📧 MailHog در آدرس http://localhost:8025 در دسترس است"


start:
	@echo "🚀 راه‌اندازی سرویس‌ها..."
	docker compose up -d


stop:
	@echo "🛑 توقف سرویس‌ها..."
	docker compose down


restart:
	@echo "🔄 راه‌اندازی مجدد..."
	docker compose restart


logs:
	@echo "📋 نمایش لاگ‌ها..."
	docker compose logs -f


clean:
	@echo "🧹 پاک کردن cache و logs..."
	docker compose exec php bin/console cache:clear
	docker compose exec php bin/console cache:warmup
	@echo "✅ پاک کردن کامل شد"


migrate:
	@echo "🗄️  اجرای migration ها..."
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction


assets:
	@echo "🎨 Build کردن assets..."
	docker compose exec php yarn install
	docker compose exec php yarn build
	docker compose exec php bin/console mautic:assets:generate


test:
	@echo "🧪 اجرای تست‌ها..."
	docker compose exec php bin/console mautic:tests:run


backup:
	@echo "💾 ایجاد backup از دیتابیس..."
	@mkdir -p backups
	docker compose exec mysql mysqldump -u mautic -pmautic_password mautic > backups/mautic_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup در پوشه backups ایجاد شد"


restore:
	@echo "📥 Restore دیتابیس..."
	@if [ -z "$(file)" ]; then \
		echo "❌ لطفاً فایل backup را مشخص کنید: make restore file=backups/mautic_20241201_120000.sql"; \
		exit 1; \
	fi
	docker compose exec -T mysql mysql -u mautic -pmautic_password mautic < $(file)
	@echo "✅ Restore کامل شد"


status:
	@echo "📊 وضعیت سرویس‌ها:"
	docker compose ps


info:
	@echo "ℹ️  اطلاعات سیستم:"
	@echo "Docker version:"
	docker --version
	@echo "Docker Compose version:"
	docker compose version
	@echo "PHP version:"
	docker compose exec php php --version
	@echo "Node version:"
	docker compose exec php node --version
	@echo "Yarn version:"
	docker compose exec php yarn --version 