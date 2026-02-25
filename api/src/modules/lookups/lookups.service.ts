import { Injectable, NotFoundException, ConflictException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Lookup } from './entities/lookup.entity';
import { CreateLookupDto, BulkCreateLookupDto } from './dto/create-lookup.dto';

@Injectable()
export class LookupsService {
  constructor(
    @InjectRepository(Lookup)
    private readonly lookupRepo: Repository<Lookup>,
  ) {}

  async create(dto: CreateLookupDto, tenantId: string): Promise<Lookup> {
    const existing = await this.lookupRepo.findOne({
      where: { tenantId, type: dto.type, name: dto.name },
    });
    if (existing) throw new ConflictException('القيمة موجودة بالفعل');

    const lookup = this.lookupRepo.create({ ...dto, tenantId });
    return this.lookupRepo.save(lookup);
  }

  async bulkCreate(dto: BulkCreateLookupDto, tenantId: string): Promise<Lookup[]> {
    const lookups = dto.names.map((name, i) =>
      this.lookupRepo.create({ type: dto.type, name, tenantId, sortOrder: i }),
    );
    return this.lookupRepo.save(lookups);
  }

  async findByType(type: string, tenantId: string): Promise<Lookup[]> {
    return this.lookupRepo.find({
      where: { tenantId, type, isActive: true },
      order: { sortOrder: 'ASC', name: 'ASC' },
    });
  }

  async findAllTypes(tenantId: string): Promise<string[]> {
    const result = await this.lookupRepo
      .createQueryBuilder('l')
      .select('DISTINCT l.type', 'type')
      .where('l.tenantId = :tenantId', { tenantId })
      .getRawMany();
    return result.map((r) => r.type);
  }

  async findOne(id: number, tenantId: string): Promise<Lookup> {
    const lookup = await this.lookupRepo.findOne({ where: { id, tenantId } });
    if (!lookup) throw new NotFoundException('القيمة غير موجودة');
    return lookup;
  }

  async update(id: number, dto: Partial<CreateLookupDto>, tenantId: string): Promise<Lookup> {
    const lookup = await this.findOne(id, tenantId);
    Object.assign(lookup, dto);
    return this.lookupRepo.save(lookup);
  }

  async remove(id: number, tenantId: string): Promise<void> {
    const lookup = await this.findOne(id, tenantId);
    lookup.isActive = false;
    await this.lookupRepo.save(lookup);
  }

  async seed(tenantId: string): Promise<{ created: number }> {
    const defaults: Record<string, string[]> = {
      city: ['إربد', 'عمان', 'الزرقاء', 'البلقاء', 'جرش', 'الطفيلة', 'عجلون', 'العقبة', 'الكرك', 'مادبا', 'معان', 'المفرق'],
      bank: ['البنك العربي', 'بنك الإسكان', 'البنك الأهلي', 'بنك الأردن', 'بنك القاهرة عمان', 'البنك الإسلامي الأردني'],
      citizen: ['أردني', 'فلسطيني', 'سوري', 'عراقي', 'مصري', 'سعودي'],
      feeling: ['راضي', 'غير راضي', 'محايد'],
      contact_type: ['هاتف', 'واتساب', 'زيارة', 'بريد إلكتروني', 'رسالة نصية'],
      connection_response: ['رد', 'لم يرد', 'مغلق', 'خارج التغطية', 'رقم خاطئ', 'وعد بالدفع', 'رفض'],
      payment_type: ['نقدي', 'شيك', 'تحويل بنكي', 'دفع إلكتروني'],
      status: ['نشط', 'معلق', 'منتهي'],
      document_type: ['هوية', 'جواز سفر', 'رخصة قيادة'],
      document_status: ['مستلم', 'مفقود', 'منتهي الصلاحية', 'مرتجع'],
      judiciary_type: ['تنفيذ', 'حقوق', 'جزاء'],
      job_type: ['قطاع خاص', 'قطاع عام', 'عمل حر', 'متقاعد', 'بدون عمل'],
      hear_about_us: ['إعلان', 'صديق', 'موظف', 'وسائل تواصل', 'أخرى'],
    };

    let created = 0;
    for (const [type, names] of Object.entries(defaults)) {
      for (let i = 0; i < names.length; i++) {
        const exists = await this.lookupRepo.findOne({ where: { tenantId, type, name: names[i] } });
        if (!exists) {
          await this.lookupRepo.save(this.lookupRepo.create({ tenantId, type, name: names[i], sortOrder: i }));
          created++;
        }
      }
    }
    return { created };
  }
}
