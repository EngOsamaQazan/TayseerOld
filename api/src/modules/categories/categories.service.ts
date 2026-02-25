import { Injectable, NotFoundException, ConflictException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Category } from './entities/category.entity';
import { CreateCategoryDto } from './dto/create-category.dto';

@Injectable()
export class CategoriesService {
  constructor(
    @InjectRepository(Category)
    private readonly categoryRepo: Repository<Category>,
  ) {}

  async create(dto: CreateCategoryDto, tenantId: string): Promise<Category> {
    const existing = await this.categoryRepo.findOne({
      where: { tenantId, type: dto.type, name: dto.name },
    });
    if (existing) throw new ConflictException('الفئة موجودة بالفعل');
    return this.categoryRepo.save(this.categoryRepo.create({ ...dto, tenantId }));
  }

  async findByType(type: string, tenantId: string): Promise<Category[]> {
    return this.categoryRepo.find({
      where: { tenantId, type, isActive: true },
      order: { name: 'ASC' },
    });
  }

  async findOne(id: number, tenantId: string): Promise<Category> {
    const cat = await this.categoryRepo.findOne({ where: { id, tenantId } });
    if (!cat) throw new NotFoundException('الفئة غير موجودة');
    return cat;
  }

  async update(id: number, dto: Partial<CreateCategoryDto>, tenantId: string): Promise<Category> {
    const cat = await this.findOne(id, tenantId);
    Object.assign(cat, dto);
    return this.categoryRepo.save(cat);
  }

  async remove(id: number, tenantId: string): Promise<void> {
    const cat = await this.findOne(id, tenantId);
    cat.isActive = false;
    await this.categoryRepo.save(cat);
  }

  async seed(tenantId: string): Promise<{ created: number }> {
    const defaults: Record<string, string[]> = {
      income: ['أقساط شهرية', 'دفعة أولى', 'تسوية', 'استرداد محكمة', 'أخرى'],
      expense: ['رواتب', 'إيجار', 'مصاريف قضائية', 'صيانة', 'مواصلات', 'اتصالات', 'أخرى'],
    };
    let created = 0;
    for (const [type, names] of Object.entries(defaults)) {
      for (const name of names) {
        const exists = await this.categoryRepo.findOne({ where: { tenantId, type, name } });
        if (!exists) {
          await this.categoryRepo.save(this.categoryRepo.create({ tenantId, type, name }));
          created++;
        }
      }
    }
    return { created };
  }
}
